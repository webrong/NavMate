<template>
  <div v-if="visible" class="auth-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="forgot-modal-title" @click.self="$emit('close')">
    <div class="auth-modal">
      <button class="auth-modal-close" @click="$emit('close')" aria-label="关闭">&times;</button>
      <h3 id="forgot-modal-title" class="auth-modal-title">忘记密码</h3>

      <div v-if="sent" class="auth-success">
        重置密码链接已发送到您的邮箱，请查收。<br />（开发期间请检查 storage/logs/laravel.log）
      </div>
      <form v-else @submit.prevent="submit">
        <div class="auth-field">
          <label>邮箱</label>
          <input v-model="email" type="email" placeholder="请输入注册邮箱" required autocomplete="email" />
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <button type="submit" class="auth-submit" :disabled="loading">
          {{ loading ? '发送中...' : '发送重置链接' }}
        </button>
      </form>

      <div class="auth-switch">
        想起密码了？<a href="#" @click.prevent="$emit('switch-to-login')">返回登录</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import request from '../../utils/request';
import { useToastStore } from '../../stores/toast';

defineProps({ visible: Boolean });
defineEmits(['close', 'switch-to-login']);

const email = ref('');
const error = ref('');
const loading = ref(false);
const sent = ref(false);
const toast = useToastStore();

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    await request.post('/api/forgot-password', { email: email.value });
    sent.value = true;
    toast.success('重置链接已发送，请查收邮箱');
  } catch (e) {
    error.value = e.response?.data?.message || '发送失败，请稍后再试';
  } finally {
    loading.value = false;
  }
}
</script>
