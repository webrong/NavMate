@props(['position' => 'fixed'])

<div class="theme-switcher {{ $position }}" style="position: {{ $position }}; top: 100px; right: 20px; z-index: 9999;">
    <div class="theme-menu">
        <button class="theme-toggle-btn" id="themeToggleBtn" title="切换主题">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
        </button>

        <div class="theme-options" id="themeOptions">
            <div class="theme-option" data-theme="orange" title="橙色主题">
                <div class="theme-preview" style="background: linear-gradient(135deg, #fc7c3c 0%, #e33636 100%);"></div>
                <span>橙色</span>
            </div>
            <div class="theme-option" data-theme="blue" title="蓝色主题">
                <div class="theme-preview" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);"></div>
                <span>蓝色</span>
            </div>
            <div class="theme-option" data-theme="green" title="绿色主题">
                <div class="theme-preview" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"></div>
                <span>绿色</span>
            </div>
            <div class="theme-option" data-theme="purple" title="紫色主题">
                <div class="theme-preview" style="background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);"></div>
                <span>紫色</span>
            </div>
            <div class="theme-option" data-theme="pink" title="粉色主题">
                <div class="theme-preview" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);"></div>
                <span>粉色</span>
            </div>
            <div class="theme-option" data-theme="dark" title="深色主题">
                <div class="theme-preview" style="background: linear-gradient(135deg, #1f2937 0%, #111827 100%);"></div>
                <span>深色</span>
            </div>
        </div>
    </div>
</div>

<style>
.theme-switcher {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.theme-toggle-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: white;
    border: 2px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    color: #484b4f;
}

.theme-toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.theme-options {
    position: absolute;
    top: 60px;
    right: 0;
    background: white;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    gap: 8px;
    min-width: 120px;
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.theme-options.show {
    display: flex;
}

.theme-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.theme-option:hover {
    background: rgba(0, 0, 0, 0.05);
}

.theme-option.active {
    background: rgba(0, 0, 0, 0.08);
    font-weight: 500;
}

.theme-preview {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 2px solid rgba(0, 0, 0, 0.1);
}

.theme-option span {
    font-size: 14px;
    color: #484b4f;
}

/* 深色主题下的主题切换器样式 */
[data-theme="dark"] .theme-toggle-btn {
    background: #374151;
    border-color: rgba(255, 255, 255, 0.1);
    color: #e5e7eb;
}

[data-theme="dark"] .theme-options {
    background: #1f2937;
    border-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .theme-option:hover {
    background: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .theme-option.active {
    background: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .theme-option span {
    color: #e5e7eb;
}
</style>

<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeOptions = document.getElementById('themeOptions');
    const themeOptions_list = document.querySelectorAll('.theme-option');

    // 从localStorage读取保存的主题
    const savedTheme = localStorage.getItem('theme') || 'orange';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateActiveTheme(savedTheme);

    // 切换主题选项显示/隐藏
    themeToggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        themeOptions.classList.toggle('show');
    });

    // 点击其他地方关闭主题选项
    document.addEventListener('click', function() {
        themeOptions.classList.remove('show');
    });

    // 主题选项点击事件
    themeOptions_list.forEach(option => {
        option.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateActiveTheme(theme);
        });
    });

    function updateActiveTheme(theme) {
        themeOptions_list.forEach(option => {
            if (option.getAttribute('data-theme') === theme) {
                option.classList.add('active');
            } else {
                option.classList.remove('active');
            }
        });
    }
});
</script>
