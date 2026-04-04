<template>
  <LoginModal
    :visible="current === 'login'"
    @close="current = null"
    @switch-to-register="current = 'register'"
  />
  <RegisterModal
    :visible="current === 'register'"
    @close="current = null"
    @switch-to-login="current = 'login'"
  />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import LoginModal from './LoginModal.vue';
import RegisterModal from './RegisterModal.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const current = ref(null);

onMounted(() => {
  if (route.query.login === 'true') current.value = 'login';
  if (route.query.register === 'true') current.value = 'register';
});

watch(() => route.query, (q) => {
  if (q.login === 'true') current.value = 'login';
  if (q.register === 'true') current.value = 'register';
});

// Close modal when user logs in
watch(() => authStore.isAuthenticated, (val) => {
  if (val) {
    current.value = null;
    // Clean query params
    if (route.query.login || route.query.register) {
      router.replace({ query: {} });
    }
  }
});
</script>
