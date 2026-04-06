<template>
  <div>
    <LoginModal
      :visible="current === 'login'"
      @close="close"
      @switch-to-register="switchTo('register')"
      @switch-to-forgot-password="switchTo('forgot-password')"
      @switch-to-verify="switchTo('verify', $event)"
    />
    <RegisterModal
      :visible="current === 'register'"
      @close="close"
      @switch-to-login="switchTo('login')"
    />
    <ForgotPasswordModal
      :visible="current === 'forgot-password'"
      @close="close"
      @switch-to-login="switchTo('login')"
    />
    <VerifyNoticeModal
      :visible="current === 'verify'"
      :email="verifyEmail"
      @close="close"
      @switch-to-login="switchTo('login')"
    />
    <ResetPasswordModal
      :visible="current === 'reset-password'"
      :token="resetToken"
      :email="resetEmail"
      @close="close"
      @switch-to-login="switchTo('login')"
      @success="onResetSuccess"
    />
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import LoginModal from './LoginModal.vue';
import RegisterModal from './RegisterModal.vue';
import ForgotPasswordModal from './ForgotPasswordModal.vue';
import VerifyNoticeModal from './VerifyNoticeModal.vue';
import ResetPasswordModal from './ResetPasswordModal.vue';

const emit = defineEmits(['close']);

const current = ref(null);
const verifyEmail = ref('');
const resetToken = ref('');
const resetEmail = ref('');

const route = useRoute();
const router = useRouter();

function switchTo(name, data) {
  if (name === 'verify' && data?.email) {
    verifyEmail.value = data.email;
  }
  current.value = name;
}

function close() {
  current.value = null;
  emit('close');
  // Clean up auth-related query params from URL
  const query = { ...route.query };
  if (query.login || query['reset-password'] || query['email-verified']) {
    delete query.login;
    delete query['reset-password'];
    delete query['email-verified'];
    delete query.token;
    delete query.email;
    router.replace({ query });
  }
}

function onResetSuccess() {
  current.value = 'login';
}

// Watch route query changes (handles both initial load and subsequent navigations)
watch(() => route.query, (query) => {
  if (query.login === 'true' && !current.value) {
    current.value = 'login';
  }
  if (query['reset-password'] === 'true') {
    resetToken.value = query.token || '';
    resetEmail.value = query.email || '';
    current.value = 'reset-password';
  }
  if (query['email-verified'] === 'true' && !current.value) {
    current.value = 'login';
  }
}, { immediate: true });

function onKeydown(e) {
  if (e.key === 'Escape' && current.value) {
    close();
  }
}

// Focus management
const previousFocus = ref(null);

watch(current, (val) => {
  if (val) {
    previousFocus.value = document.activeElement;
    nextTick(() => {
      const overlay = document.querySelector('.auth-modal-overlay');
      if (overlay) {
        const firstInput = overlay.querySelector('input, button[type="submit"]');
        if (firstInput) firstInput.focus();
      }
    });
  } else if (previousFocus.value && previousFocus.value.focus) {
    previousFocus.value.focus();
    previousFocus.value = null;
  }
});

// Focus trap: keep Tab within modal
function onTrapFocus(e) {
  if (!current.value || e.key !== 'Tab') return;
  const overlay = document.querySelector('.auth-modal-overlay');
  if (!overlay) return;
  const focusable = overlay.querySelectorAll('input, button, a[href], [tabindex]:not([tabindex="-1"])');
  if (focusable.length === 0) return;
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault();
    last.focus();
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault();
    first.focus();
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown);
  document.addEventListener('keydown', onTrapFocus);
});
onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown);
  document.removeEventListener('keydown', onTrapFocus);
});
</script>
