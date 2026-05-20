<!DOCTYPE html>
<html lang="zh-CN" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ClawBot — 微信订阅通知助手</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">

{{-- 导航 --}}
<header class="sticky top-0 z-50 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 backdrop-blur">
    <div class="mx-auto max-w-5xl px-6 flex h-14 items-center justify-between">
        <span class="font-semibold text-lg">🤖 ClawBot</span>
        <div class="flex items-center gap-3 text-sm">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-900 dark:bg-white px-4 py-1.5 text-white dark:text-zinc-900 font-medium hover:opacity-90 transition">进入后台</a>
            @else
                <a href="{{ route('login') }}" class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition">登录</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-zinc-900 dark:bg-white px-4 py-1.5 text-white dark:text-zinc-900 font-medium hover:opacity-90 transition">注册使用</a>
            @endauth
        </div>
    </div>
</header>

{{-- Hero --}}
<section class="mx-auto max-w-5xl px-6 pt-20 pb-16 text-center">
    <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 px-4 py-1 text-xs text-zinc-600 dark:text-zinc-400 mb-6">
        <span class="size-1.5 rounded-full bg-green-500"></span>
        微信 Bot 长轮询接收 · 每天定时推送
    </div>
    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-5 leading-tight">
        把重要截止日期<br>通过微信提前提醒你
    </h1>
    <p class="text-lg text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto mb-10">
        ClawBot 将你的微信账号接入为 Bot，在截止日期前自动发送倒计时通知。也可通过 Webhook URL 随时推送任意消息。
    </p>
    <div class="flex flex-wrap justify-center gap-3">
        @guest
            <a href="{{ route('register') }}" class="rounded-xl bg-zinc-900 dark:bg-white px-6 py-3 text-white dark:text-zinc-900 font-semibold hover:opacity-90 transition text-sm">免费开始使用</a>
            <a href="#how-it-works" class="rounded-xl border border-zinc-200 dark:border-zinc-700 px-6 py-3 text-zinc-700 dark:text-zinc-300 font-medium hover:bg-zinc-50 dark:hover:bg-zinc-900 transition text-sm">查看使用流程</a>
        @endguest
        @auth
            <a href="{{ route('channels.index') }}" class="rounded-xl bg-zinc-900 dark:bg-white px-6 py-3 text-white dark:text-zinc-900 font-semibold hover:opacity-90 transition text-sm">管理通道</a>
        @endauth
    </div>
</section>

{{-- 操作流程 --}}
<section id="how-it-works" class="bg-zinc-50 dark:bg-zinc-900 py-16">
    <div class="mx-auto max-w-5xl px-6">
        <h2 class="text-2xl font-bold mb-2 text-center">操作流程</h2>
        <p class="text-zinc-500 dark:text-zinc-400 text-center mb-12 text-sm">从注册到收到第一条通知，只需这几步</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['step'=>'01','icon'=>'📧','title'=>'注册账号','desc'=>'填写邮箱注册，收到验证邮件点击确认后即可使用全部功能。'],
                ['step'=>'02','icon'=>'📱','title'=>'创建微信通道','desc'=>'进入「微信通道」页面，点击创建，用手机微信扫描二维码将账号接入为 Bot。每个账号最多 3 个通道。'],
                ['step'=>'03','icon'=>'💬','title'=>'对方先发消息','desc'=>'让目标微信用户先给这个账号发一条消息，建立会话连接。这是微信 Bot 的硬性限制，只能回复，不能主动发起。'],
                ['step'=>'04','icon'=>'🔔','title'=>'创建订阅通知','desc'=>'填写标题、内容、截止日期，选择提前多少天开始通知。系统每天 12:00 和 20:00 自动发送，也可随时手动测试。'],
            ] as $item)
            <div class="relative rounded-2xl bg-white dark:bg-zinc-800 p-5 border border-zinc-100 dark:border-zinc-700">
                <div class="text-4xl font-bold text-zinc-100 dark:text-zinc-700 absolute -top-2 right-4 select-none leading-none">{{ $item['step'] }}</div>
                <div class="text-2xl mb-3">{{ $item['icon'] }}</div>
                <h3 class="font-semibold mb-2">{{ $item['title'] }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- 功能特性 --}}
<section class="py-16">
    <div class="mx-auto max-w-5xl px-6">
        <h2 class="text-2xl font-bold mb-2 text-center">主要功能</h2>
        <p class="text-zinc-500 dark:text-zinc-400 text-center mb-12 text-sm">一个账号，多种提醒方式</p>
        <div class="grid sm:grid-cols-3 gap-6">
            <div class="rounded-2xl border border-zinc-100 dark:border-zinc-800 p-6">
                <div class="size-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-xl mb-4">🗓️</div>
                <h3 class="font-semibold mb-2">截止日提醒</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">设置截止日期和提前天数（默认 30 天），进入窗口后每天早晚自动发送，支持暂停和测试发送。</p>
            </div>
            <div class="rounded-2xl border border-zinc-100 dark:border-zinc-800 p-6">
                <div class="size-10 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-xl mb-4">🔗</div>
                <h3 class="font-semibold mb-2">Webhook 推送</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">每个通道有唯一 Webhook URL，POST 请求发送消息，URL 即鉴权，无需额外 Token。</p>
            </div>
            <div class="rounded-2xl border border-zinc-100 dark:border-zinc-800 p-6">
                <div class="size-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-xl mb-4">💬</div>
                <h3 class="font-semibold mb-2">消息收发管理</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">实时接收微信入站消息，完整展示在聊天界面，可直接回复，消息记录持久保存。</p>
            </div>
        </div>
    </div>
</section>

{{-- 注意事项 --}}
<section class="bg-amber-50 dark:bg-amber-900/10 border-y border-amber-100 dark:border-amber-800/30 py-16">
    <div class="mx-auto max-w-5xl px-6">
        <div class="flex items-center gap-2 mb-8">
            <span class="text-xl">⚠️</span>
            <h2 class="text-2xl font-bold">使用前必读</h2>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach ([
                ['title'=>'微信 Bot 只能回复，不能主动发起','desc'=>'这是微信 iLink Bot 的硬性限制。目标用户必须先给 Bot 账号发一条消息，建立会话后 Bot 才能向对方发送通知。如果对方从未发消息，所有通知将无法送达。'],
                ['title'=>'通知发给最近联系人','desc'=>'订阅通知和 Webhook 消息都会发送给该通道最近一次收到入站消息的用户。如果多人和 Bot 聊天，只有最后联系的那个人会收到通知。'],
                ['title'=>'每账号最多 3 个通道','desc'=>'每个 ClawBot 账号最多可创建 3 个微信通道（即绑定 3 个微信账号）。删除通道前需先删除该通道下的所有消息订阅。'],
                ['title'=>'服务须保持持续运行','desc'=>'消息接收依赖后台的监听进程持续轮询。服务停止期间，微信收到的消息将永久丢失——微信不缓存离线消息。生产环境务必用 Supervisor 管理进程并配置自动重启。'],
            ] as $item)
            <div class="rounded-xl bg-white dark:bg-zinc-900 border border-amber-100 dark:border-amber-800/30 p-5">
                <h3 class="font-semibold text-amber-800 dark:text-amber-400 mb-2 flex items-start gap-2">
                    <span class="size-1.5 rounded-full bg-amber-500 shrink-0 mt-2"></span>
                    {{ $item['title'] }}
                </h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed pl-3.5">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Webhook --}}
<section class="py-16">
    <div class="mx-auto max-w-5xl px-6">
        <h2 class="text-2xl font-bold mb-2">Webhook 使用方式</h2>
        <p class="text-zinc-500 dark:text-zinc-400 mb-8 text-sm">在通道聊天页点击「Webhook」按钮获取专属 URL，通过 HTTP 请求即可主动推送消息</p>
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="rounded-2xl bg-zinc-900 dark:bg-zinc-800 p-5">
                <p class="text-xs text-zinc-400 mb-3 font-medium uppercase tracking-wide">POST JSON</p>
                <code class="text-green-400 text-sm leading-loose">
                    curl -X POST "https://域名/hook/TOKEN" \<br>
                    &nbsp;-H "Content-Type: application/json" \<br>
                    &nbsp;-d '{"content":"年审还有30天"}'
                </code>
            </div>
            <div class="rounded-2xl bg-zinc-900 dark:bg-zinc-800 p-5">
                <p class="text-xs text-zinc-400 mb-3 font-medium uppercase tracking-wide">POST form-data</p>
                <code class="text-green-400 text-sm leading-loose">
                    curl -X POST "https://域名/hook/TOKEN" \<br>
                    &nbsp;-d "content=年审还有30天"
                </code>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
@guest
<section class="bg-zinc-900 dark:bg-zinc-800 py-16 text-center">
    <div class="mx-auto max-w-xl px-6">
        <h2 class="text-2xl font-bold text-white mb-3">现在开始使用</h2>
        <p class="text-zinc-400 mb-8 text-sm">免费注册，扫码绑定微信，设置你的第一个到期提醒</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('register') }}" class="rounded-xl bg-white px-8 py-3 text-zinc-900 font-semibold hover:opacity-90 transition text-sm">免费注册</a>
            <a href="{{ route('login') }}" class="rounded-xl border border-zinc-700 px-8 py-3 text-zinc-300 font-medium hover:bg-zinc-800 transition text-sm">已有账号，登录</a>
        </div>
    </div>
</section>
@endguest

<footer class="border-t border-zinc-100 dark:border-zinc-800 py-8 text-center text-xs text-zinc-400">
    ClawBot &copy; {{ date('Y') }} &nbsp;·&nbsp; 基于微信 iLink Bot API 构建
</footer>

</body>
</html>
