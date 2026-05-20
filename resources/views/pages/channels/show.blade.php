<?php

use App\Models\Channel;
use App\Services\GatewayService;
use Livewire\Volt\Component;

new class extends Component {
    public Channel $channel;
    public string $newMessage = '';

    public function mount(Channel $channel): void
    {
        $this->authorize('view', $channel);
        $this->channel = $channel;
    }

    public function sendMessage(): void
    {
        $this->validate(['newMessage' => 'required|string|max:2000']);

        $lastInbound = $this->channel->messages()
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if (! $lastInbound) {
            $this->addError('newMessage', '请等待对方先发送一条消息后再回复。');

            return;
        }

        $toUser = $lastInbound->metadata['from_user'] ?? '';
        $contextToken = $lastInbound->metadata['context_token'] ?? '';

        $success = app(GatewayService::class)->sendMessage(
            $this->channel->bot_token,
            $toUser,
            $this->newMessage,
            $contextToken,
            $this->channel->baseurl
        );

        if ($success) {
            $this->channel->messages()->create([
                'content' => $this->newMessage,
                'direction' => 'outbound',
                'metadata' => ['to_user' => $toUser],
            ]);

            $this->newMessage = '';
            $this->dispatch('message-sent');
        } else {
            $this->addError('newMessage', '消息发送失败，请检查通道状态。');
        }
    }

    public function with(): array
    {
        return [
            'messages' => $this->channel->messages()->latest()->limit(100)->get()->reverse()->values(),
            'lastSender' => $this->channel->messages()->where('direction', 'inbound')->latest()->value('metadata'),
            'webhookUrl' => route('channel.webhook', $this->channel->webhook_token),
        ];
    }
}; ?>

<div class="flex h-[calc(100vh-10rem)] w-full flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-700">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <flux:icon.chat-bubble-left-right class="size-5 text-zinc-500" />
            </div>
            <div>
                <h2 class="font-semibold">{{ $channel->name }}</h2>
                <p class="text-xs text-zinc-500">
                    {{ $lastSender ? str($lastSender['from_user'] ?? '')->before('@') : '等待消息中...' }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <flux:modal.trigger name="webhook-info">
                <flux:button variant="ghost" icon="link" size="sm">Webhook</flux:button>
            </flux:modal.trigger>
            <flux:button :href="route('channels.index')" variant="ghost" icon="chevron-left">返回</flux:button>
        </div>
    </div>

    {{-- Webhook Info Modal --}}
    <flux:modal name="webhook-info" class="w-[36rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Webhook URL</flux:heading>
                <flux:subheading>通过 HTTP 请求向微信发送消息，URL 即鉴权，请勿泄露。</flux:subheading>
            </div>

            <div class="break-all rounded-lg bg-zinc-100 p-3 font-mono text-xs dark:bg-zinc-800">{{ $webhookUrl }}</div>

            <div class="space-y-2">
                <p class="text-sm font-medium">调用示例（仅支持 POST）</p>
                <div class="rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800 space-y-1">
                    <p class="text-xs text-zinc-400">curl</p>
                    <p class="break-all font-mono text-xs leading-relaxed">curl -X POST "{{ $webhookUrl }}" \<br>&nbsp;-H "Content-Type: application/json" \<br>&nbsp;-d '{"content": "消息内容"}'</p>
                </div>
                <div class="rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800 space-y-1">
                    <p class="text-xs text-zinc-400">form-data</p>
                    <p class="break-all font-mono text-xs">curl -X POST "{{ $webhookUrl }}" -d "content=消息内容"</p>
                </div>
            </div>
        </div>
    </flux:modal>

    {{-- Chat Area --}}
    <div
        class="flex-1 space-y-4 overflow-y-auto bg-zinc-50/50 p-4 dark:bg-zinc-950/50"
        id="chat-messages"
        wire:poll.2s
    >
        @forelse ($messages as $message)
            <div class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[70%] rounded-2xl px-4 py-2 {{ $message->direction === 'outbound' ? 'bg-blue-600 text-white' : 'bg-white border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700' }}">
                    @if ($message->direction === 'inbound')
                        <p class="mb-1 text-[10px] opacity-50">
                            {{ str($message->metadata['from_user'] ?? '')->before('@') ?: '未知用户' }}
                        </p>
                    @endif
                    <p class="text-sm">{{ $message->content }}</p>
                    <p class="mt-1 text-right text-[10px] opacity-50">{{ $message->created_at->format('H:i') }}</p>
                </div>
            </div>
        @empty
            <div class="flex h-full flex-col items-center justify-center text-zinc-400">
                <flux:icon.chat-bubble-left-right class="size-12 opacity-20" />
                <p class="mt-2 text-sm">等待微信用户发来第一条消息...</p>
            </div>
        @endforelse
    </div>

    {{-- Input Area --}}
    <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
        @if ($lastSender)
            <form wire:submit="sendMessage" class="flex gap-2">
                <flux:input wire:model="newMessage" placeholder="输入回复消息..." class="flex-1" />
                <flux:button type="submit" variant="primary" icon="paper-airplane" />
            </form>
            @error('newMessage')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        @else
            <p class="text-center text-sm text-zinc-400">收到第一条消息后即可回复</p>
        @endif
    </div>

    @script
    <script>
        function scrollToBottom() {
            const el = document.getElementById('chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        }

        // Scroll on initial load
        scrollToBottom();

        // Scroll after every Livewire update (covers wire:poll and sendMessage)
        Livewire.hook('commit', ({ component, respond }) => {
            respond(() => { scrollToBottom(); });
        });
    </script>
    @endscript
</div>
