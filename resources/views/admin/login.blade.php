<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>登录 - {{ config('app.name', '导航') }} 管理</title>
    @vite(['resources/css/tailwind.css', 'resources/css/app.scss'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center antialiased">
    <div class="w-full max-w-sm mx-4">
        <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-gray-950/5">
            {{-- Logo --}}
            <div class="flex items-center justify-center gap-2 mb-8">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </div>
                <span class="font-medium text-gray-950">{{ config('app.name', '导航') }}</span>
            </div>

            <form action="{{ route('admin.login') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-950 mb-1.5 block">邮箱</label>
                        <input type="email" name="email" placeholder="admin@example.com"
                               autocomplete="email"
                               class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition"
                               value="{{ old('email') }}" required>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-950 mb-1.5 block">密码</label>
                        <input type="password" name="password" placeholder="输入密码"
                               autocomplete="current-password"
                               class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition"
                               required>
                    </div>
                    @if($errors->any())
                    <div class="rounded-lg bg-red-50 text-red-600 text-sm font-medium px-4 py-2.5">
                        {{ $errors->first() }}
                    </div>
                    @endif
                    <button type="submit"
                            class="w-full h-10 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition cursor-pointer">
                        登录
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ url('/') }}" class="text-sm text-gray-500 hover:text-gray-950 no-underline transition">返回前台</a>
            </div>
        </div>
    </div>
</body>
</html>
