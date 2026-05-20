<x-layouts.app>
    @php
        $user = auth()->user();
        $channelCount = $user->channels()->count();
        $activeChannelCount = $user->channels()->where('is_active', true)->count();
        $subscriptionCount = $user->subscriptions()->count();
        $activeSubCount = $user->subscriptions()->where('is_active', true)->count();
        $todayMessageCount = \App\Models\Message::whereHas('channel', fn($q) => $q->where('user_id', $user->id))
            ->whereDate('created_at', today())
            ->count();

        // Determine which steps are done
        $hasChannel = $channelCount > 0;
        $hasInbound = $hasChannel && \App\Models\Message::whereHas('channel', fn($q) => $q->where('user_id', $user->id))
            ->where('direction', 'inbound')->exists();
        $hasSubscription = $subscriptionCount > 0;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">

        {{-- 欢迎语 --}}
        <div>
            <h1 class="text-2xl font-bold">你好，{{ $user->name }} 👋</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">欢迎回到 ClawBot 控制台</p>
        </div>

        {{-- 数据概览 --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <flux:card class="flex flex-col gap-1 p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">微信通道</p>
                <p class="text-2xl font-bold">{{ $channelCount }}</p>
                <p class="text-xs text-zinc-400">{{ $activeChannelCount }} 个活跃 / 上限 3 个</p>
            </flux:card>
            <flux:card class="flex flex-col gap-1 p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">消息订阅</p>
                <p class="text-2xl font-bold">{{ $subscriptionCount }}</p>
                <p class="text-xs text-zinc-400">{{ $activeSubCount }} 个启用中</p>
            </flux:card>
            <flux:card class="flex flex-col gap-1 p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">今日消息</p>
                <p class="text-2xl font-bold">{{ $todayMessageCount }}</p>
                <p class="text-xs text-zinc-400">收发合计</p>
            </flux:card>
            <flux:card class="flex flex-col gap-1 p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">发送时间</p>
                <p class="text-2xl font-bold">2×</p>
                <p class="text-xs text-zinc-400">每天 12:00 和 20:00</p>
            </flux:card>
        </div>

        {{-- 快速入门 / 操作进度 --}}
        <div>
            <h2 class="mb-3 text-base font-semibold">
                @if ($hasChannel && $hasInbound && $hasSubscription)
                    ✅ 一切就绪，系统正常运行中
                @else
                    🚀 快速开始
                @endif
            </h2>
            <div class="grid gap-3 sm:grid-cols-3">

                {{-- 步骤 1 --}}
                <div @class([
                    'rounded-2xl border p-5 transition',
                    'border-green-200 bg-green-50 dark:border-green-800/40 dark:bg-green-900/10' => $hasChannel,
                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 hover:border-zinc-300 dark:hover:border-zinc-600' => !$hasChannel,
                ])>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xl">📱</span>
                        @if ($hasChannel)
                            <span class="rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">已完成</span>
                        @else
                            <span class="rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-500">待完成</span>
                        @endif
                    </div>
                    <h3 class="font-semibold mb-1">创建微信通道</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4 leading-relaxed">
                        扫码将微信账号接入为 Bot，这是接收和发送消息的基础。
                    </p>
                    <flux:button href="{{ route('channels.index') }}" variant="{{ $hasChannel ? 'ghost' : 'primary' }}" size="sm" wire:navigate class="w-full">
                        {{ $hasChannel ? '管理通道 →' : '立即创建' }}
                    </flux:button>
                </div>

                {{-- 步骤 2 --}}
                <div @class([
                    'rounded-2xl border p-5 transition',
                    'border-green-200 bg-green-50 dark:border-green-800/40 dark:bg-green-900/10' => $hasInbound,
                    'border-amber-200 bg-amber-50 dark:border-amber-800/30 dark:bg-amber-900/10' => $hasChannel && !$hasInbound,
                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => !$hasChannel,
                ])>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xl">💬</span>
                        @if ($hasInbound)
                            <span class="rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">已完成</span>
                        @elseif ($hasChannel)
                            <span class="rounded-full bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400">等待中</span>
                        @else
                            <span class="rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-400">待完成</span>
                        @endif
                    </div>
                    <h3 class="font-semibold mb-1">让对方先发一条消息</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4 leading-relaxed">
                        微信 Bot 限制：只能回复，不能主动发起。目标用户需先给 Bot 发消息，才能建立会话并接收通知。
                    </p>
                    @if ($hasChannel)
                        <flux:button href="{{ route('channels.index') }}" variant="ghost" size="sm" wire:navigate class="w-full">
                            查看聊天记录 →
                        </flux:button>
                    @else
                        <flux:button disabled variant="ghost" size="sm" class="w-full">需先创建通道</flux:button>
                    @endif
                </div>

                {{-- 步骤 3 --}}
                <div @class([
                    'rounded-2xl border p-5 transition',
                    'border-green-200 bg-green-50 dark:border-green-800/40 dark:bg-green-900/10' => $hasSubscription,
                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 hover:border-zinc-300 dark:hover:border-zinc-600' => !$hasSubscription,
                ])>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xl">🔔</span>
                        @if ($hasSubscription)
                            <span class="rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">已完成</span>
                        @else
                            <span class="rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-500">待完成</span>
                        @endif
                    </div>
                    <h3 class="font-semibold mb-1">创建消息订阅</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4 leading-relaxed">
                        填写标题、提醒内容、截止日期，选择提前多少天开始通知。系统每天自动发送两次。
                    </p>
                    <flux:button href="{{ route('subscriptions.index') }}" variant="{{ $hasSubscription ? 'ghost' : 'primary' }}" size="sm" wire:navigate class="w-full">
                        {{ $hasSubscription ? '管理订阅 →' : '立即创建' }}
                    </flux:button>
                </div>

            </div>
        </div>

        {{-- 使用说明 --}}
        <flux:card class="p-5">
            <h2 class="font-semibold mb-4">📖 使用说明</h2>
            <div class="grid gap-4 sm:grid-cols-2 text-sm text-zinc-600 dark:text-zinc-400">
                <div class="space-y-2">
                    <p class="font-medium text-zinc-800 dark:text-zinc-200">Webhook 推送</p>
                    <p class="leading-relaxed">每个通道都有专属 Webhook URL（在聊天页点击「Webhook」按钮查看），仅支持 POST 请求，URL 本身即鉴权，无需额外 Token。</p>
                    <code class="block rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-700 dark:text-zinc-300 break-all">
                        POST /hook/&lt;token&gt; · {"content": "消息内容"}
                    </code>
                </div>
                <div class="space-y-2">
                    <p class="font-medium text-zinc-800 dark:text-zinc-200">订阅通知规则</p>
                    <ul class="space-y-1.5 leading-relaxed">
                        <li class="flex items-start gap-2"><span class="text-zinc-400 shrink-0 mt-0.5">·</span>进入「截止日期前 N 天」窗口后开始发送</li>
                        <li class="flex items-start gap-2"><span class="text-zinc-400 shrink-0 mt-0.5">·</span>每天 <strong class="text-zinc-700 dark:text-zinc-300">12:00</strong> 和 <strong class="text-zinc-700 dark:text-zinc-300">20:00</strong> 各发一次</li>
                        <li class="flex items-start gap-2"><span class="text-zinc-400 shrink-0 mt-0.5">·</span>截止日当天仍会发送，过期后自动停止</li>
                        <li class="flex items-start gap-2"><span class="text-zinc-400 shrink-0 mt-0.5">·</span>可随时暂停或点「测试发送」立即验证</li>
                    </ul>
                </div>
            </div>
        </flux:card>

    </div>
</x-layouts.app>
