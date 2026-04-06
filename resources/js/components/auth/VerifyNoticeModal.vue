<template>
  <div v-if="visible" class="auth-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="verify-modal-title" @click.self="$emit('close')">
    <div class="auth-modal">
      <button class="auth-modal-close" @click="$emit('close')" aria-label="关闭">&times;</button>
      <h3 id="verify-modal-title" class="auth-modal-title">验证邮箱</h3>

      <!-- Success state -->
      <div v-if="sent" class="auth-success">
        <p>验证邮件已发送到 <strong>{{ email }}</strong></p>
        <p>请在收到的邮件中点击验证链接完成验证。如果还没有收到，请检查垃圾邮件。</p>
        <div style="margin-top: 12px;">
          <a href="#" @click.prevent="resend" style="color: var(--theme-color);">重新发送</a>
        </div>
      </div>

      <!-- Form state -->
      <form v-else @submit.prevent="submit">
        <div class="auth-field">
          <label>邮箱</label>
          <input v-model="email" type="email" placeholder="请输入注册邮箱" required autocomplete="email" />
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <button type="submit" class="auth-submit" :disabled="loading">
          {{ loading ? '发送中...' : '发送验证邮件' }}
        </button>
      </form>

      <div class="auth-switch">
        <a href="#" @click.prevent="$emit('switch-to-login')">返回登录</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
  visible: Boolean,
  email: { type: String, default: '' },
});
defineEmits(['close', 'switch-to-login']);

const authStore = useAuthStore();
const email = ref(props.email || '');
const error = ref('');
const loading = ref(false);
const sent = ref(false);

watch(() => props.email, (val) => {
  if (val) email.value = val;
});

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const result = await authStore.resendVerification(email.value);
    if (result.success) {
      sent.value = true;
    } else {
      error.value = result.message;
    }
  } finally {
    loading.value = false;
  }
}

async function resend() {
  error.value = '';
  loading.value = true;
  try {
    const result = await authStore.resendVerification(email.value);
    if (!result.success) {
      error.value = result.message;
    }
  } finally {
    loading.value = false;
  }
}
</script>
