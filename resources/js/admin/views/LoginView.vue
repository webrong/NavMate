<template>
  <div class="login-page">
    <div class="login-grid">
      <!-- Left brand panel -->
      <aside class="brand-panel">
        <div class="decor-circle decor-circle--1" />
        <div class="decor-circle decor-circle--2" />
        <div class="decor-circle decor-circle--3" />
        <div class="decor-line decor-line--1" />
        <div class="decor-line decor-line--2" />

        <div class="brand-content">
          <div class="brand-logo">
            <GlobalOutlined />
          </div>
          <h1 class="brand-title">NavMate</h1>
          <p class="brand-subtitle">现代化网址导航管理系统</p>
          <div class="brand-divider" />
        </div>

        <div class="brand-footer">NavMate Admin Console · v1.1.2</div>
      </aside>

      <!-- Right login form -->
      <main class="form-panel">
        <section class="form-section">
          <div class="mobile-logo">
            <div class="mobile-logo-icon"><GlobalOutlined /></div>
            <span class="mobile-logo-text">NavMate</span>
          </div>

          <div class="form-header">
            <h2>欢迎回来</h2>
            <p>请登录管理后台</p>
          </div>

          <a-alert
            v-if="errorMsg"
            class="error-alert"
            type="error"
            show-icon
            :message="errorMsg"
          />

          <a-form :model="form" layout="vertical" @finish="handleLogin">
            <a-form-item
              name="email"
              :rules="[
                { required: true, message: '请输入邮箱' },
                { type: 'email', message: '邮箱格式不正确' },
              ]"
            >
              <template #label><span class="field-label">邮箱</span></template>
              <a-input
                v-model:value="form.email"
                size="large"
                placeholder="请输入邮箱"
                autocomplete="email"
              >
                <template #prefix><MailOutlined class="field-icon" /></template>
              </a-input>
            </a-form-item>

            <a-form-item
              name="password"
              :rules="[{ required: true, message: '请输入密码' }]"
            >
              <template #label><span class="field-label">密码</span></template>
              <a-input-password
                v-model:value="form.password"
                size="large"
                placeholder="请输入密码"
                name="password"
                autocomplete="current-password"
              >
                <template #prefix><LockOutlined class="field-icon" /></template>
              </a-input-password>
            </a-form-item>

            <div class="form-options">
              <a-checkbox v-model:checked="form.remember">记住我</a-checkbox>
              <a href="/" target="_blank" class="forgot-link">忘记密码</a>
            </div>

            <a-button
              type="primary"
              html-type="submit"
              size="large"
              block
              :loading="loading"
              class="submit-btn"
            >
              登录
            </a-button>
          </a-form>

          <p class="copyright">© 2026 NavMate. Secure admin access.</p>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAdminAuthStore } from '../stores/adminAuth';
import { GlobalOutlined, MailOutlined, LockOutlined } from '@ant-design/icons-vue';
import { message } from 'antdv-next';

const router = useRouter();
const authStore = useAdminAuthStore();
const loading = ref(false);
const errorMsg = ref('');
const form = reactive({ email: '', password: '', remember: false });

async function handleLogin() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const result = await authStore.login(form.email, form.password, form.remember);
    if (result.success !== false) {
      message.success('登录成功');
      router.push('/admin/dashboard').catch(() => {});
    } else {
      errorMsg.value = result.message || '登录失败';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #fff;
}

.login-grid {
  display: grid;
  grid-template-columns: 1fr;
  min-height: 100vh;
}

@media (min-width: 992px) {
  .login-grid {
    grid-template-columns: 1fr 1fr;
  }
}

/* ---- Left brand panel ---- */
.brand-panel {
  display: none;
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #fc7c3c, #e33636);
  color: #fff;
}

@media (min-width: 992px) {
  .brand-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }
}

.decor-circle {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}
.decor-circle--1 {
  left: -96px; top: 64px; width: 288px; height: 288px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.1);
}
.decor-circle--2 {
  bottom: 96px; right: 64px; width: 176px; height: 176px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.decor-circle--3 {
  right: -120px; top: -120px; width: 320px; height: 320px;
  background: rgba(255, 255, 255, 0.1);
  filter: blur(4px);
}

.decor-line {
  position: absolute;
  height: 1px;
  background: rgba(255, 255, 255, 0.25);
  pointer-events: none;
}
.decor-line--1 {
  left: 80px; top: 112px; width: 224px; transform: rotate(-18deg);
}
.decor-line--2 {
  bottom: 160px; left: 112px; width: 288px; transform: rotate(24deg);
  background: rgba(255, 255, 255, 0.2);
}

.brand-content {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 0 48px;
}

.brand-logo {
  width: 96px; height: 96px; margin: 0 auto 28px;
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(12px);
  display: flex; align-items: center; justify-content: center;
  font-size: 46px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.25);
}

.brand-title {
  font-size: 60px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
}

.brand-subtitle {
  margin-top: 20px;
  font-size: 18px;
  color: rgba(255, 255, 255, 0.88);
}

.brand-divider {
  width: 80px; height: 4px; margin: 32px auto 0;
  border-radius: 2px;
  background: rgba(255, 255, 255, 0.5);
}

.brand-footer {
  position: absolute;
  bottom: 32px; left: 0; right: 0;
  text-align: center;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.65);
}

/* ---- Right form panel ---- */
.form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
}

.form-section {
  width: 100%;
  max-width: 420px;
}

.mobile-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}
@media (min-width: 992px) {
  .mobile-logo { display: none; }
}
.mobile-logo-icon {
  width: 48px; height: 48px; border-radius: 16px;
  background: #fc7c3c; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px;
  box-shadow: 0 4px 16px rgba(252, 124, 60, 0.25);
}
.mobile-logo-text { font-size: 20px; font-weight: 700; color: #1f2937; }

.form-header { margin-bottom: 32px; }
.form-header h2 {
  font-size: 30px; font-weight: 600; margin: 0;
  color: #1f2937; letter-spacing: -0.01em;
}
.form-header p {
  margin: 8px 0 0; font-size: 14px; color: #6b7280;
}

.error-alert { margin-bottom: 20px; }

.field-label {
  font-size: 14px; font-weight: 500; color: #374151;
}
.field-icon { color: #9ca3af; }

.form-options {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}
.forgot-link {
  font-size: 14px; font-weight: 500; color: #fc7c3c;
}
.forgot-link:hover { color: #e33636; }

.submit-btn {
  height: 44px !important;
  font-weight: 600 !important;
  box-shadow: 0 4px 12px rgba(252, 124, 60, 0.25);
}
.submit-btn:hover {
  box-shadow: 0 6px 16px rgba(227, 54, 54, 0.3);
}

.copyright {
  margin-top: 32px; text-align: center;
  font-size: 12px; color: #9ca3af;
}
</style>
