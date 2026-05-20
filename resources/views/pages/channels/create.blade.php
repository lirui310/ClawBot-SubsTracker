<?php

use App\Models\Channel;
use App\Services\GatewayService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $description = '';
    public int $step = 1;
    public ?string $qrCode = null;    // rendered QR image URL (via qrserver.com)
    public ?string $qrcode = null;    // qrcode identifier for polling
    public string $status = 'wait';   // wait | scaned | confirmed | expired

    public function nextStep(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->authorize('create', \App\Models\Channel::class);

        $this->fetchQrCode();

        // Only advance to step 2 if the QR code was successfully fetched
        if ($this->qrcode) {
            $this->step = 2;
        }
    }

    public function fetchQrCode(): void
    {
        $data = app(GatewayService::class)->getQrCode();

        if ($data) {
            // qrcode_img_content is a WeChat scan URL, not an image — render it as a QR code via API
            $scanUrl = $data['qrcode_img_content'] ?? null;
            $this->qrCode = $scanUrl
                ? 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data='.urlencode($scanUrl)
                : null;
            $this->qrcode = $data['qrcode'] ?? null;
            $this->status = 'wait';
        } else {
            $this->addError('gateway', '无法连接到微信网关，请稍后再试。');
        }
    }

    public function checkStatus(): mixed
    {
        if (! $this->qrcode || $this->status === 'confirmed') {
            return null;
        }

        $gateway = app(GatewayService::class);
        $data = $gateway->checkQrStatus($this->qrcode);

        if ($data) {
            $this->status = $data['status'] ?? 'wait';

            if ($this->status === 'confirmed') {
                return $this->createChannel($data);
            }
        }

        return null;
    }

    protected function createChannel(array $data): mixed
    {
        Auth::user()->channels()->create([
            'name' => $this->name,
            'description' => $this->description,
            'bot_token' => $data['bot_token'] ?? null,
            'baseurl' => $data['baseurl'] ?? 'https://ilinkai.weixin.qq.com',
            'is_active' => true,
        ]);

        return redirect()->route('channels.index')->with('status', '通道创建成功！');
    }
}; ?>

<div class="mx-auto max-w-2xl pt-8">
    <flux:card>
        <div class="mb-8">
            <flux:heading size="lg">创建新通道</flux:heading>
            <flux:subheading>通过微信扫码将您的账号作为 Bot 接入</flux:subheading>
        </div>

        @if ($step === 1)
            <form wire:submit="nextStep" class="space-y-6">
                <flux:input wire:model="name" label="通道名称" placeholder="例如：我的助手" required />
                <flux:textarea wire:model="description" label="描述" placeholder="可选，简单描述通道用途" />

                @error('gateway')
                    <flux:callout variant="danger">
                        <flux:text>{{ $message }}</flux:text>
                    </flux:callout>
                @enderror

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">下一步：扫码登录</flux:button>
                </div>
            </form>
        @else
            <div class="flex flex-col items-center justify-center space-y-6 py-4" wire:poll.3s="checkStatus">
                @if ($qrCode)
                    <div class="relative rounded-xl border-4 border-zinc-100 p-2 dark:border-zinc-800">
                        <img src="{{ $qrCode }}" alt="Login QR Code" class="size-64">

                        @if ($status === 'scaned')
                            <div class="absolute inset-0 flex flex-col items-center justify-center rounded-xl bg-white/90 dark:bg-zinc-900/90">
                                <flux:icon.check-circle class="size-12 text-green-500" />
                                <p class="mt-2 font-medium">已扫码，请在手机上确认</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex size-64 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:skeleton class="size-48" />
                    </div>
                @endif

                <div class="text-center">
                    @if ($status === 'wait')
                        <p class="text-zinc-600 dark:text-zinc-400">请使用微信扫码</p>
                    @elseif ($status === 'confirmed')
                        <p class="font-medium text-green-600">登录成功，正在创建通道...</p>
                    @elseif ($status === 'expired')
                        <p class="text-red-500">二维码已过期</p>
                        <flux:button wire:click="fetchQrCode" variant="ghost" class="mt-2">重新获取</flux:button>
                    @endif
                </div>

                <flux:button wire:click="$set('step', 1)" variant="ghost">返回修改</flux:button>
            </div>
        @endif
    </flux:card>
</div>
