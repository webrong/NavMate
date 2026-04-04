<template>
  <div v-if="visible" class="auth-modal-overlay" @click.self="$emit('close')">
    <div class="auth-modal">
      <button class="auth-modal-close" @click="$emit('close')" aria-label="关闭">&times;</button>
      <h3 class="auth-modal-title">注册</h3>
      <form @submit.prevent="submit">
        <div class="auth-field">
          <label>用户名</label>
          <input v-model="name" type="text" placeholder="请输入用户名" required />
        </div>
        <div class="auth-field">
          <label>邮箱</label>
          <input v-model="email" type="email" placeholder="请输入邮箱" required autocomplete="email" />
        </div>
        <div class="auth-field">
          <label>密码</label>
          <input v-model="password" type="password" placeholder="至少8位" required autocomplete="new-password" />
        </div>
        <div class="auth-field">
          <label>确认密码</label>
          <input v-model="passwordConfirmation" type="password" placeholder="再次输入密码" required autocomplete="new-password" />
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <button type="submit" class="auth-submit" :disabled="authStore.loading">
          {{ authStore.loading ? '注册中...' : '注册' }}
        </button>
      </form>
      <div class="auth-switch">
        已有账号？<a href="#" @click.prevent="$emit('switch-to-login')">立即登录</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';

defineProps({ visible: Boolean });
defineEmits(['close', 'switch-to-login']);

const authStore = useAuthStore();
const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const error = ref('');

async function submit() {
  error.value = '';
  if (password.value !== passwordConfirmation.value) {
    error.value = '两次密码不一致';
    return;
  }
  const result = await authStore.register(name.value, email.value, password.value, passwordConfirmation.value);
  if (result.success) {
    name.value = '';
    email.value = '';
    password.value = '';
    passwordConfirmation.value = '';
  } else {
    error.value = result.message;
  }
}
</script>
