<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '后台管理') - {{ config('app.name', '导航') }}</title>
    @vite(['resources/css/tailwind.css', 'resources/css/app.scss'])
</head>
<body class="bg-gray-50 text-gray-950 min-h-screen antialiased">
    {{-- Top Bar --}}
    <header class="bg-gray-950 h-12 flex items-center justify-between px-6 sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-md bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            </div>
            <span class="text-sm font-medium text-white">{{ config('app.name', '导航') }} 管理</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs text-gray-400">{{ auth()->user()->name ?? 'Admin' }}</span>
            <a href="{{ route('admin.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-xs text-gray-500 hover:text-white no-underline transition">退出</a>
            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </header>

    <div class="flex" style="min-height: calc(100vh - 48px);">
        {{-- Sidebar --}}
        <nav class="w-48 bg-gray-950 border-r border-white/10 shrink-0 admin-sidebar overflow-y-auto hidden lg:block">
            <div class="py-3 px-2">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium no-underline rounded-lg transition @yield('nav-dashboard', 'text-gray-400 hover:text-white hover:bg-white/5')"
                   @yield('nav-dashboard-attr')>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                    控制台
                </a>
                <a href="{{ route('admin.categories.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium no-underline rounded-lg transition @yield('nav-categories', 'text-gray-400 hover:text-white hover:bg-white/5')"
                   @yield('nav-categories-attr')>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 002 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                    分类管理
                </a>
                <a href="{{ route('admin.sites.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium no-underline rounded-lg transition @yield('nav-sites', 'text-gray-400 hover:text-white hover:bg-white/5')"
                   @yield('nav-sites-attr')>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    站点管理
                </a>
                <a href="{{ route('admin.bookmarks.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium no-underline rounded-lg transition @yield('nav-bookmarks', 'text-gray-400 hover:text-white hover:bg-white/5')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                    书签导入
                </a>
                <div class="my-2 mx-3 border-t border-white/10"></div>
                <a href="{{ url('/') }}" target="_blank"
                   class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-gray-400 hover:text-white hover:bg-white/5 no-underline rounded-lg transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    查看前台
                </a>
            </div>
        </nav>

        {{-- Main Content --}}
        <main class="flex-1 p-6 overflow-auto">
            @yield('content')
        </main>
    </div>

    {{-- Toast container --}}
    <div id="admin-toast" class="fixed top-16 right-4 z-50 hidden">
        <div class="rounded-lg bg-gray-950 text-white text-sm font-medium px-4 py-2.5 shadow-xl"></div>
    </div>

    {{-- Modal container --}}
    <div id="admin-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" onclick="closeAdminModal()"></div>
        <div class="relative bg-white w-full max-w-md mx-4 z-10 rounded-2xl shadow-xl ring-1 ring-gray-950/5 overflow-hidden">
            <div class="px-6 pt-6 pb-4 flex items-center justify-between">
                <h3 id="admin-modal-title" class="text-base font-semibold text-gray-950 tracking-tight"></h3>
                <button onclick="closeAdminModal()" class="p-1 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="admin-modal-body" class="px-6 pb-6"></div>
        </div>
    </div>

    @vite(['resources/js/admin.js'])
    @yield('scripts')
</body>
</html>
