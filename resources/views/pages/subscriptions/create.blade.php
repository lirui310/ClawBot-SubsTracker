<?php

use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $title = '';
    public string $content = '';
    public string $deadline_at = '';
    public int $notify_days = 30;
    public string $channel_id = '';

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:500',
            'deadline_at' => 'required|date|after:today',
            'notify_days' => 'required|integer|min:1|max:365',
            'channel_id' => 'required|exists:channels,id',
        ], [
            'deadline_at.after' => '截止日期必须晚于今天',
            'channel_id.required' => '请选择发送通道',
        ]);

        // Ensure the channel belongs to the current user
        $channel = Auth::user()->channels()->findOrFail($this->channel_id);

        Subscription::create([
            'user_id' => Auth::id(),
            'channel_id' => $channel->id,
            'title' => $this->title,
            'content' => $this->content,
            'deadline_at' => $this->deadline_at,
            'notify_days' => $this->notify_days,
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
            <flux:heading size="lg">新建消息订阅</flux:heading>
            <flux:subheading>到期前自动通过微信发送提醒</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-5">
            <flux:input wire:model="title" label="标题" placeholder="例如：年审到期提醒" required />

            <flux:textarea
                wire:model="content"
                label="消息内容"
                placeholder="发送的具体内容（最多500字）"
                rows="4"
                required
            />

            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    wire:model="deadline_at"
                    type="date"
                    label="截止日期"
                    required
                />
                <flux:input
                    wire:model="notify_days"
                    type="number"
                    label="提前多少天开始通知"
                    min="1"
                    max="365"
                    required
                />
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
                @if ($channels->isEmpty())
                    <p class="mt-1 text-sm text-zinc-400">暂无可用通道，请先 <a href="{{ route('channels.create') }}" class="underline" wire:navigate>创建微信通道</a></p>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button href="{{ route('subscriptions.index') }}" variant="ghost" wire:navigate>取消</flux:button>
                <flux:button type="submit" variant="primary">创建订阅</flux:button>
            </div>
        </form>
    </flux:card>
</div>
