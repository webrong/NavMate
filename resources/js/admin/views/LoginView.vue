<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h2>后台管理</h2>
        <p>请使用管理员账号登录</p>
      </div>
      <a-form :model="form" @finish="handleLogin" layout="vertical" size="large">
        <a-form-item name="email" :rules="[{ required: true, message: '请输入邮箱' }, { type: 'email', message: '邮箱格式不正确' }]">
          <a-input v-model:value="form.email" placeholder="管理员邮箱" autocomplete="email">
            <template #prefix><UserOutlined /></template>
          </a-input>
        </a-form-item>
        <a-form-item name="password" :rules="[{ required: true, message: '请输入密码' }]">
          <a-input-password v-model:value="form.password" placeholder="密码" autocomplete="current-password">
            <template #prefix><LockOutlined /></template>
          </a-input-password>
        </a-form-item>
        <a-form-item>
          <a-checkbox v-model:checked="form.remember">记住我</a-checkbox>
        </a-form-item>
        <a-form-item>
          <a-button type="primary" html-type="submit" block :loading="loading">登录</a-button>
        </a-form-item>
      </a-form>
      <div class="login-footer">
        <a href="/" target="_blank">返回前台首页</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAdminAuthStore } from '../stores/adminAuth';
import { UserOutlined, LockOutlined } from '@ant-design/icons-vue';
import { message } from 'antdv-next';

const router = useRouter();
const authStore = useAdminAuthStore();
const loading = ref(false);
const form = reactive({ email: '', password: '', remember: false });

async function handleLogin() {
  loading.value = true;
  const result = await authStore.login(form.email, form.password, form.remember);
  loading.value = false;
  if (result.success !== false) {
    message.success('登录成功');
    router.push('/admin/dashboard');
  } else {
    message.error(result.message || '登录失败');
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
  width: 400px;
  padding: 40px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.login-header h2 {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 8px;
  color: #333;
}

.login-header p {
  color: #888;
  font-size: 14px;
  margin: 0;
}

.login-footer {
  text-align: center;
  margin-top: 16px;
}

.login-footer a {
  color: #1677ff;
  font-size: 14px;
}
</style>
