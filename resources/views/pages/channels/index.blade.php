<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function delete(Channel $channel): void
    {
        $response = \Illuminate\Support\Facades\Gate::inspect('delete', $channel);

        if ($response->denied()) {
            $this->addError('delete_'.$channel->id, $response->message());

            return;
        }

        $channel->delete();

        $this->dispatch('channel-deleted');
    }

    public function with(): array
    {
        return [
            'channels' => Auth::user()->channels()->latest()->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">微信通道</h1>
            <p class="text-zinc-500 dark:text-zinc-400">管理您的微信机器人通道 (上限 3 个)</p>
        </div>
        @if($channels->count() < 3)
            <flux:button href="{{ route('channels.create') }}" variant="primary" icon="plus">创建通道</flux:button>
        @else
            <flux:button disabled variant="primary" icon="plus" title="已达到最大通道数">创建通道</flux:button>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($channels as $channel)
            <flux:card class="flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">{{ $channel->name }}</h3>
                        <flux:badge :variant="$channel->is_active ? 'success' : 'warning'">
                            {{ $channel->is_active ? '已激活' : '未激活' }}
                        </flux:badge>
                    </div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $channel->description ?: '无描述' }}</p>
                    <div class="mt-4 text-xs text-zinc-400">
                        ID: {{ $channel->bot_id ?: '暂无' }}
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    <flux:button :href="route('channels.show', $channel)" variant="ghost" class="flex-1">进入聊天</flux:button>
                    <flux:modal.trigger name="delete-channel-{{ $channel->id }}">
                        <flux:button variant="danger" icon="trash" />
                    </flux:modal.trigger>
                </div>

                <flux:modal name="delete-channel-{{ $channel->id }}" class="min-w-[22rem]">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">删除通道</flux:heading>
                            <flux:subheading>此操作不可逆！将永久删除该通道的所有聊天记录和日志。</flux:subheading>
                        </div>

                        @error('delete_'.$channel->id)
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">取消</flux:button>
                            </flux:modal.close>
                            <flux:button wire:click="delete({{ $channel->id }})" variant="danger">确认删除</flux:button>
                        </div>
                    </div>
                </flux:modal>
            </flux:card>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 py-12 dark:border-zinc-700">
                <flux:icon.chat-bubble-left-right class="size-12 text-zinc-300" />
                <p class="mt-4 text-zinc-500">您还没有创建任何通道</p>
                <flux:button href="{{ route('channels.create') }}" variant="ghost" class="mt-2">立即创建第一个通道</flux:button>
            </div>
        @endforelse
    </div>
</div>
