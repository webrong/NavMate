@extends('layouts.admin')

@section('title', '站点管理')
@section('nav-sites', 'text-white bg-white/10')
@section('nav-sites-attr', '')

@section('content')
{{-- Toolbar --}}
<div class="flex items-center justify-between mb-6">
    <h1 class="text-lg font-semibold text-gray-950 tracking-tight">站点管理</h1>
    <button onclick="openSiteForm()" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 transition cursor-pointer">添加站点</button>
</div>

{{-- Table --}}
<div class="rounded-xl bg-white outline outline-gray-950/5 overflow-x-auto">
    <table class="w-full min-w-[800px]" id="sites-table">
        <thead>
            <tr class="bg-gray-50/50">
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">ID</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">站点</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">URL</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">分类</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">类型</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">状态</th>
                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">点击</th>
                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">操作</th>
            </tr>
        </thead>
        <tbody id="sites-tbody">
            {{-- Rendered by JS --}}
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div id="sites-pagination" class="mt-4 flex items-center justify-center gap-1"></div>
@endsection

@section('scripts')
<script>
(function() {
    const dataUrl = '{{ route("admin.sites.data") }}';
    const storeUrl = '{{ route("admin.sites.store") }}';
    const fetchUrl = '{{ route("admin.sites.fetch-url") }}';
    const baseUrl = '{{ url("/admin/sites") }}';
    const csrf = '{{ csrf_token() }}';
    const categories = @json($categories);

    let currentPage = 1;

    function loadTable(page) {
        currentPage = page || 1;
        fetch(dataUrl + '?page=' + currentPage, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
        })
        .then(r => r.json())
        .then(res => {
            renderTable(res.data.data || []);
            renderPagination(res.data);
        });
    }

    function renderTable(items) {
        const tbody = document.getElementById('sites-tbody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-sm text-gray-400 text-center">暂无数据</td></tr>';
            return;
        }
        tbody.innerHTML = items.map(item => `
            <tr class="border-t border-gray-950/5 hover:bg-gray-50/50 transition">
                <td class="px-6 py-3 text-sm text-gray-500">${item.id}</td>
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2">
                        ${item.favicon_url ? `<img src="${escAttr(item.favicon_url)}" class="w-5 h-5 rounded object-contain" onerror="this.style.display='none'">` : ''}
                        <span class="text-sm font-medium text-gray-950">${escHtml(item.title)}</span>
                    </div>
                </td>
                <td class="px-6 py-3">
                    <a href="${escAttr(item.url)}" target="_blank" class="text-xs text-gray-500 hover:text-indigo-600 no-underline transition truncate block max-w-[180px]">${escHtml(item.url)}</a>
                </td>
                <td class="px-6 py-3 text-sm text-gray-500">${item.category ? escHtml(item.category.name) : '-'}</td>
                <td class="px-6 py-3">
                    ${item.is_public
                        ? '<span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">公共</span>'
                        : '<span class="text-xs font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">私人</span>'}
                </td>
                <td class="px-6 py-3">
                    ${item.is_active
                        ? '<span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">启用</span>'
                        : '<span class="text-xs font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">禁用</span>'}
                </td>
                <td class="px-6 py-3 text-sm text-gray-500 text-right">${item.clicks}</td>
                <td class="px-6 py-3 text-right">
                    <button onclick="openSiteForm(${item.id}, ${escAttr(JSON.stringify(item))})" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 cursor-pointer">编辑</button>
                    <button onclick="deleteSite(${item.id})" class="text-sm font-medium text-red-500 hover:text-red-600 cursor-pointer ml-3">删除</button>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        const container = document.getElementById('sites-pagination');
        const last = data.last_page || 1;
        if (last <= 1) { container.innerHTML = ''; return; }
        let html = '';
        for (let i = 1; i <= last; i++) {
            html += `<button onclick="loadTable(${i})" class="w-8 h-8 text-sm font-medium rounded-lg cursor-pointer transition ${i === currentPage ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-100'}">${i}</button>`;
        }
        container.innerHTML = html;
    }

    window.loadTable = loadTable;

    window.openSiteForm = function(id, data) {
        const d = data || {};
        const title = id ? '编辑站点' : '添加站点';
        const url = id ? baseUrl + '/' + id : storeUrl;
        const method = id ? 'PUT' : 'POST';

        const catOptions = categories.map(c =>
            `<option value="${c.id}" ${d.category_id == c.id ? 'selected' : ''}>${escHtml(c.name)}</option>`
        ).join('');

        const body = `
            <form id="site-form" class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">URL <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="url" name="url" id="site-url-input" value="${escAttr(d.url||'')}" required placeholder="https://example.com"
                               class="flex-1 h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                        <button type="button" id="fetch-url-btn" class="shrink-0 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm font-medium text-gray-950 px-3 transition cursor-pointer">获取信息</button>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">标题 <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="${escAttr(d.title||'')}" required
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">图标URL</label>
                    <input type="url" name="favicon_url" value="${escAttr(d.favicon_url||'')}"
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">描述</label>
                    <input type="text" name="description" value="${escAttr(d.description||'')}"
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">分类 <span class="text-red-500">*</span></label>
                    <select name="category_id" required
                            class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                        <option value="">请选择</option>
                        ${catOptions}
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_public" id="site-public" ${d.is_public !== false ? 'checked' : ''}
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600/20">
                        <label for="site-public" class="text-sm font-medium text-gray-950">公共</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="site-active" ${d.is_active !== false ? 'checked' : ''}
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600/20">
                        <label for="site-active" class="text-sm font-medium text-gray-950">启用</label>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">排序</label>
                    <input type="number" name="sort_order" value="${d.sort_order||0}"
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full h-10 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition cursor-pointer">确定</button>
                </div>
            </form>
        `;

        openAdminModal(title, body);

        // Fetch URL info
        document.getElementById('fetch-url-btn').addEventListener('click', function() {
            const inputUrl = document.getElementById('site-url-input').value;
            if (!inputUrl) return;
            const btn = this;
            btn.textContent = '获取中...';
            btn.disabled = true;
            fetch(fetchUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ url: inputUrl })
            })
            .then(r => r.json())
            .then(res => {
                if (res.title) document.querySelector('#site-form input[name=title]').value = res.title;
                if (res.favicon_url) document.querySelector('#site-form input[name=favicon_url]').value = res.favicon_url;
            })
            .catch(() => showToast('获取失败'))
            .finally(() => { btn.textContent = '获取信息'; btn.disabled = false; });
        });

        // Submit form
        document.getElementById('site-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            const payload = Object.fromEntries(fd.entries());
            payload.is_public = fd.has('is_public');
            payload.is_active = fd.has('is_active');

            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.code === 0) {
                    closeAdminModal();
                    loadTable(currentPage);
                    showToast(res.msg || '操作成功');
                } else {
                    showToast(res.msg || '操作失败');
                }
            })
            .catch(() => showToast('网络错误'));
        });
    };

    window.deleteSite = function(id) {
        if (!confirm('确定删除此站点？')) return;
        fetch(baseUrl + '/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(() => { loadTable(currentPage); showToast('已删除'); })
        .catch(() => showToast('删除失败'));
    };

    function escHtml(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function escAttr(str) { return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

    loadTable(1);
})();
</script>
@endsection
