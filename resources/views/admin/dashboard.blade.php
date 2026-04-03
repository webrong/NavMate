@extends('layouts.admin')

@section('title', '控制台')
@section('nav-dashboard', 'text-white bg-white/10')
@section('nav-dashboard-attr', '')

@section('content')
{{-- Stats Grid --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="rounded-xl bg-white p-6 outline outline-gray-950/5">
        <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $stats['categories'] }}</p>
        <p class="text-xs font-medium text-gray-500 mt-1">分类总数</p>
    </div>
    <div class="rounded-xl bg-white p-6 outline outline-gray-950/5">
        <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $stats['sites'] }}</p>
        <p class="text-xs font-medium text-gray-500 mt-1">站点总数</p>
    </div>
    <div class="rounded-xl bg-white p-6 outline outline-gray-950/5">
        <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $stats['public_sites'] }}</p>
        <p class="text-xs font-medium text-gray-500 mt-1">公共站点</p>
    </div>
    <div class="rounded-xl bg-white p-6 outline outline-gray-950/5">
        <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $stats['private_sites'] }}</p>
        <p class="text-xs font-medium text-gray-500 mt-1">游客添加</p>
    </div>
    <div class="rounded-xl bg-white p-6 outline outline-gray-950/5">
        <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $stats['total_clicks'] }}</p>
        <p class="text-xs font-medium text-gray-500 mt-1">总点击</p>
    </div>
    <div class="rounded-xl bg-white p-6 outline outline-gray-950/5">
        <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $stats['today_clicks'] ?? 0 }}</p>
        <p class="text-xs font-medium text-gray-500 mt-1">今日点击</p>
    </div>
</div>

{{-- Tables --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Top Sites --}}
    <div class="rounded-xl bg-white outline outline-gray-950/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-950/5">
            <h3 class="text-sm font-semibold text-gray-950 tracking-tight">热门站点（按点击量）</h3>
        </div>
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50/50">
                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">站点</th>
                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">分类</th>
                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">点击</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topSites as $site)
                <tr class="border-t border-gray-950/5 hover:bg-gray-50/50 transition">
                    <td class="px-6 py-3 text-sm font-medium text-gray-950">{{ $site->title }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ $site->category->name ?? '-' }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500 text-right">{{ $site->clicks }}</td>
                </tr>
                @endforeach
                @if($topSites->isEmpty())
                <tr><td colspan="3" class="px-6 py-6 text-sm text-gray-400 text-center">暂无数据</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Recent Sites --}}
    <div class="rounded-xl bg-white outline outline-gray-950/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-950/5">
            <h3 class="text-sm font-semibold text-gray-950 tracking-tight">最近添加</h3>
        </div>
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50/50">
                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">站点</th>
                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">分类</th>
                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">时间</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentSites as $site)
                <tr class="border-t border-gray-950/5 hover:bg-gray-50/50 transition">
                    <td class="px-6 py-3 text-sm font-medium text-gray-950">{{ $site->title }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ $site->category->name ?? '-' }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500 text-right">{{ $site->created_at->format('m-d H:i') }}</td>
                </tr>
                @endforeach
                @if($recentSites->isEmpty())
                <tr><td colspan="3" class="px-6 py-6 text-sm text-gray-400 text-center">暂无数据</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
