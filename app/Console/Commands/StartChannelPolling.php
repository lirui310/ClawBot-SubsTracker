<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Services\GatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class StartChannelPolling extends Command
{
    protected $signature = 'channels:listen {--channel= : Worker mode: poll a single channel by ID}';

    protected $description = 'Listen for inbound WeChat messages. Runs as master (spawns per-channel workers) or worker (--channel=ID).';

    public function handle(GatewayService $gateway): void
    {
        if ($channelId = $this->option('channel')) {
            $this->runWorker($gateway, (int) $channelId);
        } else {
            $this->runMaster();
        }
    }

    // -------------------------------------------------------------------------
    // Master: watches for new / removed channels and manages worker processes
    // -------------------------------------------------------------------------

    private function runMaster(): void
    {
        $this->info('channels:listen master started — watching for channels...');

        /** @var array<int, Process> $workers */
        $workers = [];

        while (true) {
            $activeIds = Channel::where('is_active', true)->pluck('id')->all();

            // Spawn workers for new channels
            foreach ($activeIds as $id) {
                if (! isset($workers[$id]) || ! $workers[$id]->isRunning()) {
                    $workers[$id] = $this->spawnWorker($id);
                    $this->line("  [master] Started worker for channel {$id}");
                }
            }

            // Stop workers for channels that were deactivated or deleted
            foreach (array_keys($workers) as $id) {
                if (! in_array($id, $activeIds)) {
                    $workers[$id]->stop(3);
                    unset($workers[$id]);
                    $this->line("  [master] Stopped worker for channel {$id}");
                }
            }

            sleep(5);
        }
    }

    private function spawnWorker(int $channelId): Process
    {
        $process = new Process([PHP_BINARY, base_path('artisan'), 'channels:listen', "--channel={$channelId}"]);
        $process->setTimeout(null);
        $process->start(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process;
    }

    // -------------------------------------------------------------------------
    // Worker: polls one channel forever until it is deactivated / deleted
    // -------------------------------------------------------------------------

    private function runWorker(GatewayService $gateway, int $channelId): void
    {
        $channel = Channel::find($channelId);

        if (! $channel || ! $channel->is_active) {
            return;
        }

        $this->line("  [worker:{$channelId}] Polling started for {$channel->name}");

        while (true) {
            $channel = Channel::find($channelId);

            if (! $channel || ! $channel->is_active) {
                $this->line("  [worker:{$channelId}] Channel inactive — stopping.");

                return;
            }

            try {
                $data = $gateway->getUpdates(
                    $channel->bot_token,
                    $channel->poll_buf ?? '',
                    $channel->baseurl
                );
            } catch (\Exception $e) {
                Log::error("channels:listen worker — exception for channel {$channelId}: ".$e->getMessage());
                sleep(5);
                continue;
            }

            if (! $data) {
                Log::warning("channels:listen worker — getupdates null for channel {$channelId}");
                sleep(5);
                continue;
            }

            $saved = $this->processMessages($channel, $data['msgs'] ?? []);

            if ($saved > 0) {
                $this->line("  [worker:{$channelId}] Received {$saved} message(s).");
            }

            $channel->poll_buf = $data['get_updates_buf'] ?? $channel->poll_buf;
            $channel->save();
        }
    }

    /** @param array<mixed> $messages */
    private function processMessages(Channel $channel, array $messages): int
    {
        $saved = 0;

        foreach ($messages as $msg) {
            $fromUser = $msg['from_user_id'] ?? '';
            $contextToken = $msg['context_token'] ?? '';

            if ($fromUser === '' || str_ends_with($fromUser, '@im.bot')) {
                continue;
            }

            $content = '';
            foreach ($msg['item_list'] ?? [] as $item) {
                if (($item['type'] ?? 0) === 1) {
                    $content = $item['text_item']['text'] ?? '';
                    break;
                }
            }

            if ($content === '') {
                continue;
            }

            $channel->messages()->create([
                'content' => $content,
                'direction' => 'inbound',
                'metadata' => [
                    'from_user' => $fromUser,
                    'context_token' => $contextToken,
                ],
            ]);

            $saved++;
        }

        return $saved;
    }
}
