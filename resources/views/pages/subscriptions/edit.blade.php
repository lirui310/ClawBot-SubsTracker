<?php

use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public Subscription $subscription;

    public string $title = '';
    public string $content = '';
    public string $deadline_at = '';
    public int $notify_days = 30;
    public string $channel_id = '';

    public function mount(Subscription $subscription): void
    {
        abort_unless($subscription->user_id === Auth::id(), 403);

        $this->subscription = $subscription;
        $this->title = $subscription->title;
        $this->content = $subscription->content;
        $this->deadline_at = $subscription->deadline_at->format('Y-m-d');
        $this->notify_days = $subscription->notify_days;
        $this->channel_id = (string) $subscription->channel_id;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:500',
            'deadline_at' => 'required|date',
            'notify_days' => 'required|integer|min:1|max:365',
            'channel_id' => 'required|exists:channels,id',
        ], [
            'channel_id.required' => '请选择发送通道',
        ]);

        Auth::user()->channels()->findOrFail($this->channel_id);

        $this->subscription->update([
            'title' => $this->title,
            'content' => $this->content,
            'deadline_at' => $this->deadline_at,
            'notify_days' => $this->notify_days,
            'channel_id' => $this->channel_id,
        ]);

        redirect()->route('subscriptions.index');
    }

    public function with(): array
    {
        return [
            'channels' => Auth::user()->channels()->where('is_active', true)->get(),
        ];
    }
}; ?>

<div class="mx-auto max-w-2xl pt-8">
    <flux:card>
        <div class="mb-6">
            <flux:heading size="lg">编辑消息订阅</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-5">
            <flux:input wire:model="title" label="标题" required />

            <flux:textarea wire:model="content" label="消息内容" rows="4" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="deadline_at" type="date" label="截止日期" required />
                <flux:input wire:model="notify_days" type="number" label="提前多少天开始通知" min="1" max="365" required />
            </div>

            <div>
                <flux:label>发送通道</flux:label>
                <flux:select wire:model="channel_id" class="mt-1">
                    <option value="">请选择微信通道...</option>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                    @endforeach
                </flux:select>
                @error('channel_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button href="{{ route('subscriptions.index') }}" variant="ghost" wire:navigate>取消</flux:button>
                <flux:button type="submit" variant="primary">保存修改</flux:button>
            </div>
        </form>
    </flux:card>
</div>
