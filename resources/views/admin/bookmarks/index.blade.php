@extends('layouts.admin')

@section('title', '书签导入')
@section('nav-bookmarks', 'text-white bg-white/10')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-lg font-semibold text-gray-950 tracking-tight">书签导入</h1>
    </div>

    {{-- Step 1: Upload --}}
    <div id="step-upload" class="rounded-xl bg-white outline outline-gray-950/5 p-6 mb-4">
        <h2 class="text-sm font-semibold text-gray-950 mb-3">1. 上传书签文件</h2>
        <p class="text-xs text-gray-500 mb-4">
            从浏览器导出书签文件（.html），支持 Chrome / Edge / Firefox / Safari。<br>
            导出方法：浏览器书签管理器 → 导出书签 → 保存为 HTML 文件
        </p>

        <div id="drop-zone" class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center transition hover:border-indigo-300 hover:bg-indigo-50/30 cursor-pointer"
             onclick="document.getElementById('bookmark-file').click()"
             ondragover="event.preventDefault(); this.classList.add('border-indigo-400','bg-indigo-50/50')"
             ondragleave="this.classList.remove('border-indigo-400','bg-indigo-50/50')"
             ondrop="event.preventDefault(); this.classList.remove('border-indigo-400','bg-indigo-50/50'); handleFiles(event.dataTransfer.files)">
            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            <p class="text-sm text-gray-500">点击或拖拽文件到此处</p>
            <p class="text-xs text-gray-400 mt-1">支持 .html / .htm，最大 10MB</p>
            <input type="file" id="bookmark-file" accept=".html,.htm" class="hidden" onchange="handleFiles(this.files)">
        </div>
    </div>

    {{-- Step 2: Preview --}}
    <div id="step-preview" class="hidden">
        <div class="rounded-xl bg-white outline outline-gray-950/5 p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-950 mb-3">2. 预览解析结果</h2>

            <div id="preview-stats" class="grid grid-cols-2 gap-3 mb-4"></div>

            <div class="mb-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="skip-duplicate" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-gray-700">跳过已存在的重复链接</span>
                </label>
            </div>

            <div id="preview-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
        </div>

        {{-- Step 3: Import --}}
        <div class="flex items-center gap-3">
            <button id="btn-import" onclick="doImport()" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2.5 transition cursor-pointer">
                确认导入
            </button>
            <button onclick="resetUpload()" class="rounded-lg bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 outline outline-gray-950/10 transition cursor-pointer">
                重新选择
            </button>
        </div>
    </div>

    {{-- Result --}}
    <div id="step-result" class="hidden">
        <div class="rounded-xl bg-white outline outline-gray-950/5 p-6 mb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-950">导入完成</h2>
                    <p id="result-message" class="text-xs text-gray-500"></p>
                </div>
            </div>
            <div id="result-stats" class="grid grid-cols-3 gap-3"></div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sites.index') }}" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2.5 transition no-underline inline-block">
                查看站点
            </a>
            <button onclick="resetUpload()" class="rounded-lg bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 outline outline-gray-950/10 transition cursor-pointer">
                继续导入
            </button>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl px-8 py-6 shadow-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span id="loading-text" class="text-sm font-medium text-gray-950">解析中...</span>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showLoading(text = '解析中...') {
    document.getElementById('loading-text').textContent = text;
    document.getElementById('loading-overlay').classList.remove('hidden');
}
function hideLoading() {
    document.getElementById('loading-overlay').classList.add('hidden');
}

function handleFiles(files) {
    if (!files || !files.length) return;
    const file = files[0];
    if (!file.name.match(/\.(html|htm)$/i)) {
        showToast('请选择 .html 或 .htm 格式的书签文件');
        return;
    }
    previewFile(file);
}

async function previewFile(file) {
    showLoading('解析书签文件...');

    const formData = new FormData();
    formData.append('bookmark_file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const res = await fetch('{{ route("admin.bookmarks.preview") }}', {
            method: 'POST',
            body: formData,
        });
        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.error || '解析失败');
        }

        renderPreview(data);
    } catch (e) {
        showToast(e.message);
    } finally {
        hideLoading();
    }
}

function renderPreview(data) {
    const { stats, preview } = data;

    // Stats
    document.getElementById('preview-stats').innerHTML = `
        <div class="rounded-lg bg-indigo-50 px-4 py-3">
            <div class="text-2xl font-bold text-indigo-600">${stats.total_bookmarks}</div>
            <div class="text-xs text-indigo-500">个书签</div>
        </div>
        <div class="rounded-lg bg-violet-50 px-4 py-3">
            <div class="text-2xl font-bold text-violet-600">${stats.total_folders}</div>
            <div class="text-xs text-violet-500">个文件夹</div>
        </div>
    `;

    // Preview list
    let html = '';
    preview.forEach(group => {
        const samples = group.samples.map(s =>
            `<span class="inline-block text-xs text-gray-500 bg-gray-100 rounded px-2 py-0.5 mr-1 mb-1">${escHtml(s.title)}</span>`
        ).join('');

        html += `
        <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50/50 hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center outline outline-gray-950/5 shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-950">${escHtml(group.folder || '未分类')}</div>
                <div class="text-xs text-gray-400 mt-0.5">${group.count} 个书签</div>
                <div class="mt-1 flex flex-wrap">${samples}</div>
            </div>
        </div>`;
    });

    document.getElementById('preview-list').innerHTML = html;

    // Show preview step
    document.getElementById('step-upload').classList.add('hidden');
    document.getElementById('step-preview').classList.remove('hidden');
    document.getElementById('step-result').classList.add('hidden');
}

async function doImport() {
    showLoading('正在导入...');

    try {
        const res = await fetch('{{ route("admin.bookmarks.import") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                skip_duplicate: document.getElementById('skip-duplicate').checked,
            }),
        });
        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.error || '导入失败');
        }

        renderResult(data);
    } catch (e) {
        showToast(e.message);
    } finally {
        hideLoading();
    }
}

function renderResult(data) {
    const { result, message } = data;

    document.getElementById('result-message').textContent = message;
    document.getElementById('result-stats').innerHTML = `
        <div class="rounded-lg bg-green-50 px-4 py-3 text-center">
            <div class="text-2xl font-bold text-green-600">${result.categories}</div>
            <div class="text-xs text-green-500">新增分类</div>
        </div>
        <div class="rounded-lg bg-blue-50 px-4 py-3 text-center">
            <div class="text-2xl font-bold text-blue-600">${result.sites}</div>
            <div class="text-xs text-blue-500">新增站点</div>
        </div>
        <div class="rounded-lg bg-gray-100 px-4 py-3 text-center">
            <div class="text-2xl font-bold text-gray-500">${result.skipped}</div>
            <div class="text-xs text-gray-400">跳过重复</div>
        </div>
    `;

    document.getElementById('step-preview').classList.add('hidden');
    document.getElementById('step-result').classList.remove('hidden');
}

function resetUpload() {
    document.getElementById('step-upload').classList.remove('hidden');
    document.getElementById('step-preview').classList.add('hidden');
    document.getElementById('step-result').classList.add('hidden');
    document.getElementById('bookmark-file').value = '';
}

function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showToast(msg) {
    const toast = document.getElementById('admin-toast');
    toast.querySelector('div').textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}
</script>
@endsection
