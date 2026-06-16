<!DOCTYPE html>
<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - {{ config('app.name', '导航') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f0f2f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .wizard {
            width: 100%;
            max-width: 720px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .wizard-header {
            background: linear-gradient(135deg, #1677ff 0%, #4096ff 100%);
            color: #fff;
            padding: 28px 32px;
            text-align: center;
        }
        .wizard-header h1 { font-size: 22px; font-weight: 600; margin-bottom: 4px; }
        .wizard-header p { font-size: 13px; opacity: 0.85; }

        /* Steps indicator */
        .steps-bar {
            display: flex;
            padding: 20px 32px 0;
            gap: 4px;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            padding-bottom: 12px;
            font-size: 12px;
            color: #999;
            cursor: pointer;
        }
        .step-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #f0f0f0;
            border-radius: 2px;
        }
        .step-item.active { color: #1677ff; font-weight: 500; }
        .step-item.active::after { background: #1677ff; }
        .step-item.done { color: #52c41a; }
        .step-item.done::after { background: #52c41a; }
        .step-num {
            display: inline-block;
            width: 22px;
            height: 22px;
            line-height: 22px;
            border-radius: 50%;
            background: #f0f0f0;
            color: #999;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .step-item.active .step-num { background: #1677ff; color: #fff; }
        .step-item.done .step-num { background: #52c41a; color: #fff; }

        /* Content area */
        .wizard-body { padding: 24px 32px 32px; }
        .step-panel { display: none; }
        .step-panel.active { display: block; }
        .step-title { font-size: 17px; font-weight: 600; margin-bottom: 16px; color: #1a1a1a; }

        /* Form elements */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #555;
            margin-bottom: 6px;
        }
        .form-group label .required { color: #ff4d4f; margin-left: 2px; }
        .form-input {
            width: 100%;
            height: 38px;
            padding: 0 12px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input:focus { border-color: #1677ff; box-shadow: 0 0 0 2px rgba(22,119,255,0.1); }
        .form-input::placeholder { color: #bbb; }
        textarea.form-input { height: auto; padding: 8px 12px; resize: vertical; }
        select.form-input { cursor: pointer; appearance: auto; }
        .form-row { display: flex; gap: 16px; }
        .form-row .form-group { flex: 1; }
        .form-hint { font-size: 12px; color: #999; margin-top: 4px; }
        .form-error { font-size: 12px; color: #ff4d4f; margin-top: 4px; }

        /* Radio group */
        .radio-group { display: flex; gap: 16px; margin-bottom: 16px; }
        .radio-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        .radio-item:hover { border-color: #1677ff; }
        .radio-item.selected { border-color: #1677ff; background: #f0f5ff; }
        .radio-item input { display: none; }

        /* Checkbox */
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 8px;
        }
        .checkbox-item input { width: 16px; height: 16px; accent-color: #1677ff; cursor: pointer; }

        /* Check result tables */
        .check-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .check-table th {
            text-align: left;
            padding: 8px 12px;
            background: #fafafa;
            font-size: 13px;
            font-weight: 500;
            color: #666;
            border-bottom: 1px solid #f0f0f0;
        }
        .check-table td {
            padding: 8px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
        }
        .check-pass { color: #52c41a; font-weight: 500; }
        .check-fail { color: #ff4d4f; font-weight: 500; }
        .check-optional { color: #faad14; font-weight: 500; }
        .check-category {
            font-weight: 600;
            color: #1a1a1a;
            padding: 10px 12px 6px !important;
            font-size: 14px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 36px;
            padding: 0 20px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
            color: #333;
        }
        .btn:hover { border-color: #1677ff; color: #1677ff; }
        .btn-primary {
            background: #1677ff;
            border-color: #1677ff;
            color: #fff;
        }
        .btn-primary:hover { background: #4096ff; border-color: #4096ff; color: #fff; }
        .btn-primary:disabled { background: #91caff; border-color: #91caff; cursor: not-allowed; }
        .btn-test {
            padding: 0 16px;
            height: 38px;
            background: #f6ffed;
            border-color: #b7eb8f;
            color: #52c41a;
            white-space: nowrap;
        }
        .btn-test:hover { border-color: #52c41a; color: #389e0d; }
        .btn-test.loading { opacity: 0.6; pointer-events: none; }

        /* Footer */
        .wizard-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            margin-top: 8px;
        }

        /* Test result */
        .test-result {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
        }
        .test-result.success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .test-result.error { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }

        /* Progress overlay */
        .install-progress {
            text-align: center;
            padding: 40px 20px;
        }
        .install-progress .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #f0f0f0;
            border-top-color: #1677ff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .install-progress .progress-text {
            font-size: 15px;
            color: #555;
            margin-bottom: 8px;
        }
        .install-progress .progress-step {
            font-size: 13px;
            color: #999;
        }

        /* Success */
        .install-success {
            text-align: center;
            padding: 40px 20px;
        }
        .install-success .success-icon {
            width: 64px;
            height: 64px;
            background: #52c41a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: #fff;
        }
        .install-success h2 { font-size: 20px; margin-bottom: 8px; color: #1a1a1a; }
        .install-success p { color: #666; font-size: 14px; margin-bottom: 24px; }

        /* Conditional fields */
        .mysql-fields, .redis-fields { display: none; }
        .mysql-fields.show, .redis-fields.show { display: block; }

        /* Mail section */
        .mail-section { display: none; }
        .mail-section.show { display: block; }

        /* Responsive */
        @media (max-width: 640px) {
            body { padding: 12px; }
            .wizard-body { padding: 20px; }
            .wizard-header { padding: 20px; }
            .steps-bar { padding: 16px 20px 0; }
            .step-item { font-size: 11px; }
            .form-row { flex-direction: column; gap: 0; }
            .radio-group { flex-direction: column; gap: 8px; }
        }
    </style>
</head>
<body>
    <div class="wizard">
        <div class="wizard-header">
            <h1>安装向导</h1>
            <p>欢迎使用导航站点，跟随引导完成初始配置</p>
        </div>

        <div class="steps-bar">
            <div class="step-item active" data-step="1">
                <div class="step-num">1</div><br>环境检查
            </div>
            <div class="step-item" data-step="2">
                <div class="step-num">2</div><br>数据库
            </div>
            <div class="step-item" data-step="3">
                <div class="step-num">3</div><br>缓存
            </div>
            <div class="step-item" data-step="4">
                <div class="step-num">4</div><br>站点与管理员
            </div>
            <div class="step-item" data-step="5">
                <div class="step-num">5</div><br>邮件
            </div>
            <div class="step-item" data-step="6">
                <div class="step-num">6</div><br>安装
            </div>
        </div>

        <div class="wizard-body">
            <form id="install-form">
            <meta name="csrf-token" content="{{ csrf_token() }}">

            <!-- Step 1: Environment Check -->
            <div class="step-panel active" id="step-1">
                <div class="step-title">环境检查</div>
                <div id="env-checks">
                    <p style="color: #999; font-size: 14px;">正在检测服务器环境...</p>
                </div>
                <div class="wizard-footer">
                    <div></div>
                    <button class="btn btn-primary" id="btn-step1-next" disabled data-action="next">下一步</button>
                </div>
            </div>

            <!-- Step 2: Database -->
            <div class="step-panel" id="step-2">
                <div class="step-title">数据库配置</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>主机地址<span class="required">*</span></label>
                        <input type="text" class="form-input" id="db-host" value="127.0.0.1" placeholder="数据库主机">
                    </div>
                    <div class="form-group" style="max-width: 120px;">
                        <label>端口<span class="required">*</span></label>
                        <input type="number" class="form-input" id="db-port" value="3306">
                    </div>
                </div>
                <div class="form-group">
                    <label>数据库名<span class="required">*</span></label>
                    <input type="text" class="form-input" id="db-database" placeholder="数据库名称">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>用户名<span class="required">*</span></label>
                        <input type="text" class="form-input" id="db-username" placeholder="数据库用户名">
                    </div>
                    <div class="form-group">
                        <label>密码</label>
                        <input type="password" class="form-input" id="db-password" placeholder="数据库密码">
                    </div>
                </div>

                <div style="margin-top: 8px;">
                    <button class="btn btn-test" id="btn-test-db" data-action="test-db">测试连接</button>
                    <div id="db-test-result"></div>
                </div>

                <div class="wizard-footer">
                    <button class="btn" data-action="prev">上一步</button>
                    <button class="btn btn-primary" data-action="next">下一步</button>
                </div>
            </div>

            <!-- Step 3: Cache -->
            <div class="step-panel" id="step-3">
                <div class="step-title">缓存配置</div>
                <div class="form-group">
                    <label>缓存驱动</label>
                    <div class="radio-group" id="cache-type-group">
                        <label class="radio-item selected" data-cache="database">
                            <input type="radio" name="cache_store" value="database" checked>
                            Database（默认）
                        </label>
                        <label class="radio-item" data-cache="redis">
                            <input type="radio" name="cache_store" value="redis">
                            Redis
                        </label>
                        <label class="radio-item" data-cache="file">
                            <input type="radio" name="cache_store" value="file">
                            File
                        </label>
                    </div>
                </div>

                <div class="redis-fields" id="redis-fields">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Redis 主机</label>
                            <input type="text" class="form-input" id="redis-host" value="127.0.0.1">
                        </div>
                        <div class="form-group" style="max-width: 120px;">
                            <label>端口</label>
                            <input type="number" class="form-input" id="redis-port" value="6379">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>密码</label>
                        <input type="password" class="form-input" id="redis-password" placeholder="留空则无密码">
                    </div>
                    <button class="btn btn-test" id="btn-test-redis" data-action="test-redis">测试连接</button>
                    <div id="redis-test-result"></div>
                </div>

                <div class="form-hint" style="margin-top: 4px;">Database 缓存使用已有数据库，无需额外配置。Redis 性能更好但需要安装 Redis 服务。</div>

                <div class="wizard-footer">
                    <button class="btn" data-action="prev">上一步</button>
                    <button class="btn btn-primary" data-action="next">下一步</button>
                </div>
            </div>

            <!-- Step 4: Site & Admin -->
            <div class="step-panel" id="step-4">
                <div class="step-title">站点信息与管理员</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>站点名称<span class="required">*</span></label>
                        <input type="text" class="form-input" id="app-name" value="NavMate" placeholder="站点名称">
                    </div>
                    <div class="form-group">
                        <label>站点 URL<span class="required">*</span></label>
                        <input type="text" class="form-input" id="app-url" value="{{ request()->schemeAndHttpHost() }}" placeholder="https://example.com">
                    </div>
                </div>

                <div style="border-top: 1px solid #f0f0f0; margin: 20px 0 16px; padding-top: 16px;">
                    <div style="font-size: 14px; font-weight: 500; color: #555; margin-bottom: 12px;">管理员账号</div>
                </div>
                <div class="form-group">
                    <label>管理员昵称<span class="required">*</span></label>
                    <input type="text" class="form-input" id="admin-name" value="Admin" placeholder="显示昵称">
                </div>
                <div class="form-group">
                    <label>登录用户名<span class="required">*</span></label>
                    <input type="text" class="form-input" id="admin-username" value="admin" placeholder="登录用户名（字母数字下划线）">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>密码<span class="required">*</span></label>
                        <input type="password" class="form-input" id="admin-password" placeholder="至少8位">
                    </div>
                    <div class="form-group">
                        <label>确认密码<span class="required">*</span></label>
                        <input type="password" class="form-input" id="admin-password-confirm" placeholder="再次输入密码">
                    </div>
                </div>

                <label class="checkbox-item" style="margin-top: 12px;">
                    <input type="checkbox" id="seed-sample" checked>
                    安装示例导航数据（推荐，安装后可删除）
                </label>

                <div class="wizard-footer">
                    <button class="btn" data-action="prev">上一步</button>
                    <button class="btn btn-primary" data-action="next">下一步</button>
                </div>
            </div>

            <!-- Step 5: Mail -->
            <div class="step-panel" id="step-5">
                <div class="step-title">邮件配置（可选）</div>

                <label class="checkbox-item" style="margin-bottom: 16px;">
                    <input type="checkbox" id="skip-mail" checked>
                    跳过邮件配置（可在后台"系统设置 > 邮件配置"中设置）
                </label>

                <div class="mail-section" id="mail-section">
                    <div class="form-group">
                        <label>邮箱预设</label>
                        <select class="form-input" id="mail-preset">
                            <option value="">自定义</option>
                            <option value="qq">QQ 邮箱</option>
                            <option value="163">163 邮箱</option>
                            <option value="126">126 邮箱</option>
                            <option value="gmail">Gmail</option>
                            <option value="outlook">Outlook</option>
                            <option value="ali">阿里企业邮</option>
                            <option value="tencent">腾讯企业邮</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP 服务器</label>
                            <input type="text" class="form-input" id="mail-host" placeholder="如 smtp.qq.com">
                        </div>
                        <div class="form-group" style="max-width: 120px;">
                            <label>端口</label>
                            <input type="number" class="form-input" id="mail-port" value="465">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>加密方式</label>
                        <select class="form-input" id="mail-encryption">
                            <option value="ssl">SSL</option>
                            <option value="tls">TLS</option>
                            <option value="null">无加密</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" class="form-input" id="mail-username" placeholder="邮箱地址">
                        </div>
                        <div class="form-group">
                            <label>授权码 / 密码</label>
                            <input type="password" class="form-input" id="mail-password" placeholder="授权码">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>发件人地址</label>
                            <input type="email" class="form-input" id="mail-from-address" placeholder="留空使用用户名">
                        </div>
                        <div class="form-group">
                            <label>发件人名称</label>
                            <input type="text" class="form-input" id="mail-from-name" placeholder="留空使用站点名称">
                        </div>
                    </div>
                </div>

                <div class="wizard-footer">
                    <button class="btn" data-action="prev">上一步</button>
                    <button class="btn btn-primary" data-action="next">下一步</button>
                </div>
            </div>

            <!-- Step 6: Execute -->
            <div class="step-panel" id="step-6">
                <div id="install-progress" class="install-progress">
                    <div class="spinner"></div>
                    <div class="progress-text">正在安装...</div>
                    <div class="progress-step" id="install-step-text">准备中</div>
                </div>
                <div id="install-success" class="install-success" style="display:none;">
                    <div class="success-icon">&#10003;</div>
                    <h2>安装完成！</h2>
                    <p>导航站点已成功安装，点击下方按钮进入管理后台</p>
                    <a href="/admin/login" class="btn btn-primary" style="text-decoration:none;">进入管理后台</a>
                </div>
                <div id="install-error" style="display:none; text-align:center; padding: 40px 20px;">
                    <div style="font-size:48px; color:#ff4d4f; margin-bottom:16px;">&#10007;</div>
                    <h2 style="font-size:20px; margin-bottom:8px;">安装失败</h2>
                    <p id="install-error-msg" style="color:#666; font-size:14px; margin-bottom:24px;"></p>
                    <button class="btn" data-action="restart">重新开始</button>
                </div>
            </div>
            </form>
        </div>
    </div>

<script nonce="{{ $cspNonce ?? '' }}">
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function esc(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

async function post(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
        },
        body: JSON.stringify(data),
    });
    if (!res.ok && res.status !== 422 && res.status !== 500) {
        const text = await res.text();
        throw new Error(text || `HTTP ${res.status}`);
    }
    return res.json();
}

const mailPresets = {
    qq:      { host: 'smtp.qq.com',           port: 465, encryption: 'ssl' },
    '163':   { host: 'smtp.163.com',          port: 465, encryption: 'ssl' },
    '126':   { host: 'smtp.126.com',          port: 465, encryption: 'ssl' },
    gmail:   { host: 'smtp.gmail.com',        port: 587, encryption: 'tls' },
    outlook: { host: 'smtp.office365.com',    port: 587, encryption: 'tls' },
    ali:     { host: 'smtp.qiye.aliyun.com',  port: 465, encryption: 'ssl' },
    tencent: { host: 'smtp.exmail.qq.com',    port: 465, encryption: 'ssl' },
};

const wizard = {
    currentStep: 1,
    totalSteps: 6,

    init() {
        this.bindEvents();
        this.checkEnvironment();
    },

    bindEvents() {
        document.getElementById('install-form').addEventListener('submit', function(e) {
            e.preventDefault();
        });

        document.getElementById('install-form').addEventListener('click', function(e) {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const action = btn.dataset.action;
            switch (action) {
                case 'next': wizard.next(); break;
                case 'prev': wizard.prev(); break;
                case 'test-db': wizard.testDatabase(); break;
                case 'test-redis': wizard.testRedis(); break;
                case 'restart': wizard.goTo(1); break;
            }
        });

        document.getElementById('cache-type-group').addEventListener('click', function(e) {
            const item = e.target.closest('[data-cache]');
            if (!item) return;
            wizard.selectCache(item.dataset.cache, item);
        });

        document.getElementById('skip-mail').addEventListener('change', function() {
            wizard.toggleMail();
        });

        document.getElementById('mail-preset').addEventListener('change', function() {
            wizard.applyMailPreset(this.value);
        });
    },

    goTo(step) {
        document.querySelectorAll('.step-item').forEach((el, i) => {
            el.classList.remove('active', 'done');
            if (i + 1 < step) el.classList.add('done');
            if (i + 1 === step) el.classList.add('active');
        });
        document.querySelectorAll('.step-panel').forEach((el, i) => {
            el.classList.toggle('active', i + 1 === step);
        });
        this.currentStep = step;
    },

    next() {
        if (this.currentStep === 4 && !this.validateStep4()) return;
        if (this.currentStep === 5 && !this.validateStep5()) return;
        if (this.currentStep === 5) {
            this.goTo(6);
            this.executeInstall();
            return;
        }
        if (this.currentStep < this.totalSteps) {
            this.goTo(this.currentStep + 1);
        }
    },

    prev() {
        if (this.currentStep > 1) {
            this.goTo(this.currentStep - 1);
        }
    },

    async checkEnvironment() {
        const res = await post('/install/check-environment');
        const container = document.getElementById('env-checks');
        const btnNext = document.getElementById('btn-step1-next');

        if (!res.checks) {
            container.innerHTML = '<p style="color:#ff4d4f">环境检测失败，请检查服务器配置</p>';
            return;
        }

        let html = '';
        res.checks.forEach(group => {
            html += '<table class="check-table">';
            html += `<tr><td colspan="3" class="check-category">${esc(group.category)}</td></tr>`;
            group.items.forEach(item => {
                const statusClass = item.pass ? 'check-pass' : (item.optional ? 'check-optional' : 'check-fail');
                const statusText = item.pass ? '&#10003; 通过' : (item.optional ? '&#9888; 未安装（可选）' : '&#10007; 未通过');
                html += `<tr><td>${esc(item.label)}</td><td>${esc(item.value)}</td><td class="${statusClass}">${statusText}</td></tr>`;
            });
            html += '</table>';
        });

        container.innerHTML = html;

        if (res.all_pass) {
            btnNext.disabled = false;
        } else {
            container.innerHTML += '<div class="form-error" style="margin-top:8px;">存在未通过的必需项，请修复后刷新页面重试</div>';
            btnNext.disabled = true;
        }
    },

    async testDatabase() {
        const btn = document.getElementById('btn-test-db');
        const resultEl = document.getElementById('db-test-result');
        btn.classList.add('loading');
        btn.textContent = '测试中...';
        resultEl.innerHTML = '';

        const data = {
            db_host: document.getElementById('db-host').value,
            db_port: parseInt(document.getElementById('db-port').value),
            db_database: document.getElementById('db-database').value,
            db_username: document.getElementById('db-username').value,
            db_password: document.getElementById('db-password').value,
        };

        const res = await post('/install/test-database', data);
        const cls = res.success ? 'success' : 'error';
        let html = `<div class="test-result ${cls}">${esc(res.message)}</div>`;

        if (res.existing_data && res.existing_data.has_data) {
            const items = esc(res.existing_data.summary.join('、'));
            html += `<div class="test-result success" style="margin-top:8px;">
                <strong>检测到已有数据：</strong>${items}
                <br><span style="color:#666;font-size:12px;">重新安装不会覆盖或丢失这些数据（已存在的记录会自动跳过）</span>
            </div>`;
        }

        resultEl.innerHTML = html;

        btn.classList.remove('loading');
        btn.textContent = '测试连接';
    },

    selectCache(type, el) {
        document.querySelectorAll('#cache-type-group .radio-item').forEach(item => item.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('redis-fields').classList.toggle('show', type === 'redis');
    },

    async testRedis() {
        const btn = document.getElementById('btn-test-redis');
        const resultEl = document.getElementById('redis-test-result');
        btn.classList.add('loading');
        btn.textContent = '测试中...';
        resultEl.innerHTML = '';

        const data = {
            redis_host: document.getElementById('redis-host').value,
            redis_port: parseInt(document.getElementById('redis-port').value),
            redis_password: document.getElementById('redis-password').value || null,
        };

        const res = await post('/install/test-redis', data);
        const cls = res.success ? 'success' : 'error';
        resultEl.innerHTML = `<div class="test-result ${cls}">${esc(res.message)}</div>`;

        btn.classList.remove('loading');
        btn.textContent = '测试连接';
    },

    toggleMail() {
        const skip = document.getElementById('skip-mail').checked;
        document.getElementById('mail-section').classList.toggle('show', !skip);
    },

    applyMailPreset(key) {
        const preset = mailPresets[key];
        if (!preset) return;
        document.getElementById('mail-host').value = preset.host;
        document.getElementById('mail-port').value = preset.port;
        document.getElementById('mail-encryption').value = preset.encryption;
    },

    validateStep4() {
        const name = document.getElementById('admin-name').value.trim();
        const username = document.getElementById('admin-username').value.trim();
        const pwd = document.getElementById('admin-password').value;
        const pwd2 = document.getElementById('admin-password-confirm').value;

        if (!name) { alert('请输入管理员用户名'); return false; }
        if (!username) { alert('请输入登录用户名'); return false; }
        if (pwd.length < 8) { alert('密码至少8位'); return false; }
        if (pwd !== pwd2) { alert('两次密码不一致'); return false; }
        return true;
    },

    validateStep5() { return true; },

    async executeInstall() {
        const steps = ['准备中', '写入配置文件', '生成密钥', '运行数据库迁移', '创建管理员账号', '初始化设置', '完成'];
        let stepIdx = 0;

        const stepText = document.getElementById('install-step-text');
        const progressEl = document.getElementById('install-progress');
        const successEl = document.getElementById('install-success');
        const errorEl = document.getElementById('install-error');

        const advanceStep = () => {
            stepIdx++;
            if (stepIdx < steps.length) {
                stepText.textContent = steps[stepIdx];
            }
        };

        const timer = setInterval(advanceStep, 1200);

        const cacheType = document.querySelector('input[name="cache_store"]:checked').value;

        const data = {
            db_host: document.getElementById('db-host').value,
            db_port: parseInt(document.getElementById('db-port').value),
            db_database: document.getElementById('db-database').value,
            db_username: document.getElementById('db-username').value,
            db_password: document.getElementById('db-password').value,
            cache_store: cacheType,
            app_name: document.getElementById('app-name').value.trim(),
            app_url: document.getElementById('app-url').value.trim(),
            admin_name: document.getElementById('admin-name').value.trim(),
            admin_username: document.getElementById('admin-username').value.trim(),
            admin_password: document.getElementById('admin-password').value,
            seed_sample: document.getElementById('seed-sample').checked,
            skip_mail: document.getElementById('skip-mail').checked,
        };

        if (cacheType === 'redis') {
            data.redis_host = document.getElementById('redis-host').value;
            data.redis_port = parseInt(document.getElementById('redis-port').value);
            data.redis_password = document.getElementById('redis-password').value || null;
        }

        if (!data.skip_mail) {
            data.mail_host = document.getElementById('mail-host').value;
            data.mail_port = parseInt(document.getElementById('mail-port').value);
            data.mail_encryption = document.getElementById('mail-encryption').value;
            data.mail_username = document.getElementById('mail-username').value;
            data.mail_password = document.getElementById('mail-password').value;
            data.mail_from_address = document.getElementById('mail-from-address').value;
            data.mail_from_name = document.getElementById('mail-from-name').value;
        }

        try {
            const res = await post('/install/execute', data);
            clearInterval(timer);

            if (res.success) {
                progressEl.style.display = 'none';
                successEl.style.display = 'block';
            } else {
                progressEl.style.display = 'none';
                errorEl.style.display = 'block';
                document.getElementById('install-error-msg').textContent = res.message;
            }
        } catch (e) {
            clearInterval(timer);
            progressEl.style.display = 'none';
            errorEl.style.display = 'block';
            document.getElementById('install-error-msg').textContent = '请求失败: ' + e.message;
        }
    },
};

wizard.init();
</script>
</body>
</html>
