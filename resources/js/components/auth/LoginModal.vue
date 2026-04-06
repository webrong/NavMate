<template>
  <div v-if="visible" class="auth-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="login-modal-title" @click.self="$emit('close')">
    <div class="auth-modal">
      <button class="auth-modal-close" @click="$emit('close')" aria-label="关闭">&times;</button>
      <h3 id="login-modal-title" class="auth-modal-title">登录</h3>
      <form @submit.prevent="submit">
        <div class="auth-field">
          <label>邮箱</label>
          <input v-model="email" type="email" placeholder="请输入邮箱" required autocomplete="email" />
        </div>
        <div class="auth-field">
          <label>密码</label>
          <div class="auth-password-field">
            <input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="请输入密码" required autocomplete="current-password" />
            <button type="button" class="auth-password-toggle" @click="showPassword = !showPassword" :aria-label="showPassword ? '隐藏密码' : '显示密码'">
              <svg v-if="showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <div v-if="unverifiedEmail" class="auth-warning">
          邮箱未验证，<a href="#" @click.prevent="resendVerification">重新发送验证邮件</a>
          <span v-if="resendSent" class="auth-warning-sent">✓ 已发送</span>
        </div>
        <div class="auth-forgot">
          <a href="#" @click.prevent="$emit('switch-to-forgot-password')">忘记密码？</a>
        </div>
        <button type="submit" class="auth-submit" :disabled="authStore.loading">
          {{ authStore.loading ? '登录中...' : '登录' }}
        </button>
      </form>
      <div class="auth-switch">
        还没有账号？<a href="#" @click.prevent="$emit('switch-to-register')">立即注册</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

defineProps({ visible: Boolean });
const emit = defineEmits(['close', 'switch-to-register', 'switch-to-forgot-password', 'switch-to-verify']);

const authStore = useAuthStore();
const toast = useToastStore();
const email = ref('');
const password = ref('');
const error = ref('');
const showPassword = ref(false);
const unverifiedEmail = ref('');
const resendSent = ref(false);

async function submit() {
  error.value = '';
  unverifiedEmail.value = '';
  const result = await authStore.login(email.value, password.value);
  if (result.success) {
    email.value = '';
    password.value = '';
    toast.success('登录成功');
    emit('close');
  } else {
    error.value = result.message;
    if (result.unverified && result.email) {
      unverifiedEmail.value = result.email;
    }
  }
}

async function resendVerification() {
  if (!unverifiedEmail.value) return;
  const result = await authStore.resendVerification(unverifiedEmail.value);
  if (result.success) {
    resendSent.value = true;
    toast.success('验证邮件已发送');
    setTimeout(() => { resendSent.value = false; }, 3000);
  }
}
</script>
