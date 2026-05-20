<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\GatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionNotifications extends Command
{
    protected $signature = 'subscriptions:send {--id= : Send a specific subscription immediately (for test)}';

    protected $description = 'Send due subscription notifications. Scheduled at 12:00 and 20:00 daily.';

    public function handle(GatewayService $gateway): void
    {
        if ($id = $this->option('id')) {
            $subscription = Subscription::findOrFail((int) $id);
            $this->send($gateway, $subscription);

            return;
        }

        // Pre-filter in SQL: only subscriptions whose deadline hasn't passed yet
        // The notify_days window check (deadline - notify_days <= today) is done in PHP
        // because it requires per-row arithmetic that varies by column value
        Subscription::where('is_active', true)
            ->whereDate('deadline_at', '>=', today())
            ->with('channel')
            ->get()
            ->filter(fn (Subscription $s) => $s->isInNotifyWindow())
            ->each(fn (Subscription $s) => $this->send($gateway, $s));
    }

    private function send(GatewayService $gateway, Subscription $subscription): void
    {
        $channel = $subscription->channel;

        if (! $channel || ! $channel->is_active) {
            $this->line("  [skip] Subscription {$subscription->id}: channel inactive or missing.");

            return;
        }

        $lastInbound = $channel->messages()->where('direction', 'inbound')->latest()->first();

        if (! $lastInbound) {
            $this->line("  [skip] Subscription {$subscription->id}: WeChat user hasn't messaged the channel yet.");
            Log::warning("subscriptions:send — no inbound message for subscription {$subscription->id}");

            return;
        }

        $toUser = $lastInbound->metadata['from_user'] ?? '';
        $contextToken = $lastInbound->metadata['context_token'] ?? '';
        $days = $subscription->daysUntilDeadline();

        $daysText = match (true) {
            $days > 0 => "距截止还有 {$days} 天",
            $days === 0 => '今天是截止日',
            default => '已超过截止日',
        };

        $message = "【{$subscription->title}】\n{$subscription->content}\n\n{$daysText}";

        $success = $gateway->sendMessage(
            $channel->bot_token,
            $toUser,
            $message,
            $contextToken,
            $channel->baseurl
        );

        if ($success) {
            $subscription->update(['last_sent_at' => now()]);

            $channel->messages()->create([
                'content' => $message,
                'direction' => 'outbound',
                'metadata' => [
                    'to_user' => $toUser,
                    'via' => 'subscription',
                    'subscription_id' => $subscription->id,
                ],
            ]);

            $this->line("  [sent] «{$subscription->title}» → {$toUser}");
        } else {
            $this->line("  [fail] Subscription {$subscription->id}: iLink send failed.");
            Log::error("subscriptions:send — send failed for subscription {$subscription->id}");
        }
    }
}
