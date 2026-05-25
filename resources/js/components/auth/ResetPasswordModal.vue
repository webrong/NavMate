<template>
  <div v-if="visible" class="auth-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="reset-modal-title">
    <div class="auth-modal">
      <button class="auth-modal-close" @click="$emit('close')" aria-label="关闭">&times;</button>
      <h3 id="reset-modal-title" class="auth-modal-title">重置密码</h3>
      <form @submit.prevent="submit">
        <div class="auth-field">
          <label>新密码</label>
          <input v-model="password" type="password" placeholder="请输入新密码（至少8位）" required autocomplete="new-password" />
          <PasswordStrength :password="password" />
        </div>
        <div class="auth-field">
          <label>确认密码</label>
          <input v-model="passwordConfirmation" type="password" placeholder="再次输入新密码" required autocomplete="new-password" />
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <button type="submit" class="auth-submit" :disabled="loading">
          {{ loading ? '重置中...' : '重置密码' }}
        </button>
      </form>
      <div class="auth-switch">
        <a href="#" @click.prevent="$emit('switch-to-login')">返回登录</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import PasswordStrength from '../PasswordStrength.vue';

const props = defineProps({
  visible: Boolean,
  token: { type: String, default: '' },
  email: { type: String, default: '' },
});
const emit = defineEmits(['close', 'switch-to-login', 'success']);

const authStore = useAuthStore();
const toast = useToastStore();
const password = ref('');
const passwordConfirmation = ref('');
const error = ref('');
const loading = ref(false);

async function submit() {
  error.value = '';
  if (password.value !== passwordConfirmation.value) {
    error.value = '两次密码不一致';
    return;
  }
  if (password.value.length < 8) {
    error.value = '密码至少8位';
    return;
  }
  loading.value = true;
  try {
    const result = await authStore.resetPassword(
      props.email,
      password.value,
      passwordConfirmation.value,
      props.token,
    );
    if (result.success) {
      toast.success('密码重置成功，请登录');
      emit('success');
    } else {
      error.value = result.message;
    }
  } finally {
    loading.value = false;
  }
}
</script>
