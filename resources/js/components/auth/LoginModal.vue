<template>
  <div v-if="visible" class="auth-modal-overlay" @click.self="$emit('close')">
    <div class="auth-modal">
      <button class="auth-modal-close" @click="$emit('close')" aria-label="关闭">&times;</button>
      <h3 class="auth-modal-title">登录</h3>
      <form @submit.prevent="submit">
        <div class="auth-field">
          <label>邮箱</label>
          <input v-model="email" type="email" placeholder="请输入邮箱" required autocomplete="email" />
        </div>
        <div class="auth-field">
          <label>密码</label>
          <input v-model="password" type="password" placeholder="请输入密码" required autocomplete="current-password" />
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
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

defineProps({ visible: Boolean });
defineEmits(['close', 'switch-to-register']);

const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const error = ref('');

async function submit() {
  error.value = '';
  const result = await authStore.login(email.value, password.value);
  if (result.success) {
    email.value = '';
    password.value = '';
  } else {
    error.value = result.message;
  }
}
</script>
