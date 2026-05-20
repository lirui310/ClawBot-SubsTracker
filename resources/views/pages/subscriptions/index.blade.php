<?php

use App\Models\Subscription;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function delete(Subscription $subscription): void
    {
        abort_unless($subscription->user_id === Auth::id(), 403);
        $subscription->delete();
    }

    public function toggle(Subscription $subscription): void
    {
        abort_unless($subscription->user_id === Auth::id(), 403);
        $subscription->update(['is_active' => ! $subscription->is_active]);
    }

    public function testSend(Subscription $subscription): void
    {
        abort_unless($subscription->user_id === Auth::id(), 403);

        // Push to queue — sendMessage() is a 10s HTTP call, cannot block the web request
        $subscriptionId = $subscription->id;
        dispatch(static function () use ($subscriptionId) {
            \Illuminate\Support\Facades\Artisan::call('subscriptions:send', ['--id' => $subscriptionId]);
        });

        session()->flash('test_sent', $subscription->id);
    }

    public function with(): array
    {
        return [
            'subscriptions' => Auth::user()
                ->subscriptions()
                ->with('channel')
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">消息订阅</h1>
            <p class="text-zinc-500 dark:text-zinc-400">到期前自动发送微信提醒，每天 12:00 和 20:00 各发一次</p>
        </div>
        <flux:button href="{{ route('subscriptions.create') }}" variant="primary" icon="plus" wire:navigate>新建订阅</flux:button>
    </div>

    <div class="space-y-3">
        @forelse ($subscriptions as $subscription)
            @php
                $days = $subscription->daysUntilDeadline();
                $inWindow = $subscription->isInNotifyWindow();
            @endphp
            <flux:card class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $subscription->title }}</span>

                        @if (! $subscription->is_active)
                            <flux:badge variant="zinc">已停用</flux:badge>
                        @elseif ($days < 0)
                            <flux:badge variant="zinc">已过期</flux:badge>
                        @elseif ($inWindow)
                            <flux:badge variant="success">通知中</flux:badge>
                        @else
                            <flux:badge variant="warning">待通知</flux:badge>
                        @endif

                        <flux:badge variant="outline" class="text-xs">{{ $subscription->channel->name ?? '—' }}</flux:badge>
                    </div>

                    <p class="mt-1 line-clamp-1 text-sm text-zinc-500">{{ $subscription->content }}</p>

                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-400">
                        <span>截止：{{ $subscription->deadline_at->format('Y-m-d') }}</span>
                        <span>
                            @if ($days >= 0)
                                还有 {{ $days }} 天
                            @else
                                已过期 {{ abs($days) }} 天
                            @endif
                        </span>
                        <span>提前 {{ $subscription->notify_days }} 天开始通知</span>
                        @if ($subscription->last_sent_at)
                            <span>上次发送：{{ $subscription->last_sent_at->format('m-d H:i') }}</span>
                        @endif
                    </div>

                    @if (session('test_sent') == $subscription->id)
                        <p class="mt-1 text-xs text-green-600">测试消息已发送</p>
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <flux:button
                        wire:click="testSend({{ $subscription->id }})"
                        wire:confirm="立即发送一条测试通知？"
                        variant="ghost"
                        size="sm"
                        icon="paper-airplane"
                        title="测试发送"
                    />
                    <flux:button
                        wire:click="toggle({{ $subscription->id }})"
                        variant="ghost"
                        size="sm"
                        :icon="$subscription->is_active ? 'pause' : 'play'"
                        :title="$subscription->is_active ? '暂停' : '启用'"
                    />
                    <flux:button
                        href="{{ route('subscriptions.edit', $subscription) }}"
                        variant="ghost"
                        size="sm"
                        icon="pencil"
                        wire:navigate
                    />
                    <flux:modal.trigger name="del-sub-{{ $subscription->id }}">
                        <flux:button variant="ghost" size="sm" icon="trash" />
                    </flux:modal.trigger>
                </div>

                <flux:modal name="del-sub-{{ $subscription->id }}" class="min-w-[20rem]">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">删除订阅</flux:heading>
                            <flux:subheading>确认删除「{{ $subscription->title }}」？此操作不可恢复。</flux:subheading>
                        </div>
                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button variant="ghost">取消</flux:button>
                            </flux:modal.close>
                            <flux:button wire:click="delete({{ $subscription->id }})" variant="danger">确认删除</flux:button>
                        </div>
                    </div>
                </flux:modal>
            </flux:card>
        @empty
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 py-16 dark:border-zinc-700">
                <flux:icon.bell class="size-12 text-zinc-300" />
                <p class="mt-4 text-zinc-500">还没有任何消息订阅</p>
                <flux:button href="{{ route('subscriptions.create') }}" variant="ghost" class="mt-2" wire:navigate>立即创建</flux:button>
            </div>
        @endforelse
    </div>
</div>
