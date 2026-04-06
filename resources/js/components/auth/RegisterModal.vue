<template>
  <div v-if="visible" class="auth-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="register-modal-title" @click.self="$emit('close')">
    <div class="auth-modal">
      <button class="auth-modal-close" @click="$emit('close')" aria-label="关闭">&times;</button>
      <h3 id="register-modal-title" class="auth-modal-title">注册</h3>

      <!-- Success state after registration -->
      <div v-if="registered" class="auth-success">
        <p>注册成功！验证邮件已发送到 <strong>{{ registeredEmail }}</strong></p>
        <p>请检查您的收件箱（包括垃圾邮件）。如果没有收到，请点击下方"重新发送"。</p>
        <div style="margin-top: 12px;">
          <a href="#" @click.prevent="resendVerification" style="color: var(--theme-color);">重新发送验证邮件</a>
        </div>
      </div>

      <!-- Registration form -->
      <form v-else @submit.prevent="submit">
        <div class="auth-field">
          <label>用户名</label>
          <input v-model="name" type="text" placeholder="请输入用户名" required @blur="touched.name = true" />
          <div v-if="touched.name && nameError" class="auth-hint auth-hint-error">{{ nameError }}</div>
        </div>
        <div class="auth-field">
          <label>邮箱</label>
          <input v-model="email" type="email" placeholder="请输入邮箱" required autocomplete="email" @blur="touched.email = true" />
          <div v-if="touched.email && emailError" class="auth-hint auth-hint-error">{{ emailError }}</div>
        </div>
        <div class="auth-field">
          <label>密码</label>
          <input v-model="password" type="password" placeholder="至少8位，需含大小写字母、数字及特殊字符" required autocomplete="new-password" @blur="touched.password = true" />
          <PasswordStrength :password="password" />
          <div v-if="touched.password && passwordError" class="auth-hint auth-hint-error">{{ passwordError }}</div>
        </div>
        <div class="auth-field">
          <label>确认密码</label>
          <input v-model="passwordConfirmation" type="password" placeholder="再次输入密码" required autocomplete="new-password" @blur="touched.confirm = true" />
          <div v-if="touched.confirm && confirmError" class="auth-hint auth-hint-error">{{ confirmError }}</div>
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>
        <button type="submit" class="auth-submit" :disabled="loading">
          {{ loading ? '注册中...' : '注册' }}
        </button>
      </form>
      <div class="auth-switch">
        已有账号？ <a href="#" @click.prevent="$emit('switch-to-login')">立即登录</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import PasswordStrength from '../PasswordStrength.vue';

defineProps({ visible: Boolean });
defineEmits(['close', 'switch-to-login']);

const authStore = useAuthStore();
const toast = useToastStore();
const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const error = ref('');
const loading = ref(false);
const registered = ref(false);
const registeredEmail = ref('');
const touched = reactive({ name: false, email: false, password: false, confirm: false });

const nameError = computed(() => {
  if (!name.value) return '请输入用户名';
  if (name.value.length > 255) return '用户名不能超过255个字符';
  return '';
});

const emailError = computed(() => {
  if (!email.value) return '请输入邮箱';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) return '邮箱格式不正确';
  return '';
});

const passwordError = computed(() => {
  if (!password.value) return '请输入密码';
  if (password.value.length < 8) return '密码至少8位';
  if (!/[a-z]/.test(password.value)) return '需包含小写字母';
  if (!/[A-Z]/.test(password.value)) return '需包含大写字母';
  if (!/\d/.test(password.value)) return '需包含数字';
  if (!/[^a-zA-Z0-9]/.test(password.value)) return '需包含特殊字符';
  return '';
});

const confirmError = computed(() => {
  if (!passwordConfirmation.value) return '请确认密码';
  if (password.value !== passwordConfirmation.value) return '两次密码不一致';
  return '';
});

async function submit() {
  error.value = '';
  touched.name = true;
  touched.email = true;
  touched.password = true;
  touched.confirm = true;

  if (nameError.value || emailError.value || passwordError.value || confirmError.value) return;

  loading.value = true;
  const result = await authStore.register(name.value, email.value, password.value, passwordConfirmation.value);
  loading.value = false;
  if (result.success) {
    registeredEmail.value = email.value;
    registered.value = true;
    toast.success('注册成功，请查收验证邮件');
  } else {
    error.value = result.message;
  }
}

async function resendVerification() {
  if (!registeredEmail.value) return;
  const result = await authStore.resendVerification(registeredEmail.value);
  if (result.success) {
    toast.success('验证邮件已重新发送');
  } else {
    toast.error(result.message);
  }
}
</script>
