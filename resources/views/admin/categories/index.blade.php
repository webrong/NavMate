@extends('layouts.admin')

@section('title', '分类管理')
@section('nav-categories', 'text-white bg-white/10')
@section('nav-categories-attr', '')

@section('content')
{{-- Toolbar --}}
<div class="flex items-center justify-between mb-6">
    <h1 class="text-lg font-semibold text-gray-950 tracking-tight">分类管理</h1>
    <button onclick="openCategoryForm()" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 transition cursor-pointer">添加分类</button>
</div>

{{-- Table --}}
<div class="rounded-xl bg-white outline outline-gray-950/5 overflow-hidden">
    <table class="w-full" id="categories-table">
        <thead>
            <tr class="bg-gray-50/50">
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">ID</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">名称</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">Slug</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">排序</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">状态</th>
                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wide px-6 py-3">操作</th>
            </tr>
        </thead>
        <tbody id="categories-tbody">
            {{-- Rendered by JS --}}
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div id="categories-pagination" class="mt-4 flex items-center justify-center gap-1"></div>
@endsection

@section('scripts')
<script>
(function() {
    const dataUrl = '{{ route("admin.categories.data") }}';
    const storeUrl = '{{ route("admin.categories.store") }}';
    const baseUrl = '{{ url("/admin/categories") }}';
    const csrf = '{{ csrf_token() }}';

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
        const tbody = document.getElementById('categories-tbody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-sm text-gray-400 text-center">暂无数据</td></tr>';
            return;
        }
        tbody.innerHTML = items.map(item => `
            <tr class="border-t border-gray-950/5 hover:bg-gray-50/50 transition">
                <td class="px-6 py-3 text-sm text-gray-500">${item.id}</td>
                <td class="px-6 py-3 text-sm font-medium text-gray-950">${escHtml(item.name)}</td>
                <td class="px-6 py-3 text-sm text-gray-500">${escHtml(item.slug)}</td>
                <td class="px-6 py-3 text-sm text-gray-500">${item.sort_order}</td>
                <td class="px-6 py-3">
                    ${item.is_active
                        ? '<span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">启用</span>'
                        : '<span class="text-xs font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">禁用</span>'}
                </td>
                <td class="px-6 py-3 text-right">
                    <button onclick="openCategoryForm(${item.id}, ${escAttr(JSON.stringify(item))})" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 cursor-pointer">编辑</button>
                    <button onclick="deleteCategory(${item.id})" class="text-sm font-medium text-red-500 hover:text-red-600 cursor-pointer ml-3">删除</button>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        const container = document.getElementById('categories-pagination');
        const last = data.last_page || 1;
        if (last <= 1) { container.innerHTML = ''; return; }
        let html = '';
        for (let i = 1; i <= last; i++) {
            html += `<button onclick="loadTable(${i})" class="w-8 h-8 text-sm font-medium rounded-lg cursor-pointer transition ${i === currentPage ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-100'}">${i}</button>`;
        }
        container.innerHTML = html;
    }

    // Make accessible globally for inline onclick
    window.loadTable = loadTable;

    window.openCategoryForm = function(id, data) {
        const d = data || {};
        const title = id ? '编辑分类' : '添加分类';
        const url = id ? baseUrl + '/' + id : storeUrl;
        const method = id ? 'PUT' : 'POST';

        const body = `
            <form id="category-form" class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">名称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="${escAttr(d.name||'')}" required
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" value="${escAttr(d.slug||'')}" required
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">描述</label>
                    <input type="text" name="description" value="${escAttr(d.description||'')}"
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-950 mb-1.5 block">排序</label>
                    <input type="number" name="sort_order" value="${d.sort_order||0}"
                           class="w-full h-10 rounded-lg bg-gray-50 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-600/20 focus:bg-white transition">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="cat-active" ${d.is_active !== false ? 'checked' : ''}
                           class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600/20">
                    <label for="cat-active" class="text-sm font-medium text-gray-950">启用</label>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full h-10 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition cursor-pointer">确定</button>
                </div>
            </form>
        `;

        openAdminModal(title, body);

        document.getElementById('category-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            const payload = Object.fromEntries(fd.entries());
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

    window.deleteCategory = function(id) {
        if (!confirm('确定删除此分类？关联站点也会被删除')) return;
        fetch(baseUrl + '/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(() => { loadTable(currentPage); showToast('已删除'); })
        .catch(() => showToast('删除失败'));
    };

    // Helper
    function escHtml(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function escAttr(str) { return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

    // Init
    loadTable(1);
})();
</script>
@endsection
