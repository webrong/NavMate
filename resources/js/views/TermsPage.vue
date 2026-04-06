<template>
  <div class="terms-page">
    <div class="terms-banner">
      <div class="banner-orb banner-orb-1"></div>
      <div class="banner-orb banner-orb-2"></div>
      <h1 class="terms-title">{{ title }}</h1>
    </div>
    <div class="terms-content" v-if="siteSettings.settings.terms_content">
      <div class="terms-card" v-html="sanitizedTerms"></div>
    </div>
    <div class="terms-empty" v-else>
      <div class="terms-card">
        <p style="color: var(--muted-color); text-align: center; padding: 40px 0">暂无内容</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useSiteSettingsStore } from '../stores/siteSettings';
import { sanitizeHtml } from '../composables/useSanitize';

const route = useRoute();
const siteSettings = useSiteSettingsStore();

const title = computed(() => {
  if (route.path === '/terms') return '免责声明';
  return '用户协议';
});

const sanitizedTerms = computed(() => sanitizeHtml(siteSettings.settings.terms_content));
</script>

<style scoped>
.terms-banner {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: calc(var(--main-nav-height, 56px) + 40px) 20px 40px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.banner-orb {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  opacity: 0.35;
  filter: blur(40px);
}

.banner-orb-1 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(255,255,255,0.5) 0%, transparent 70%);
  top: -20%; left: 10%;
  animation: orb-drift-1 16s ease-in-out infinite;
}

.banner-orb-2 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(255,200,100,0.4) 0%, transparent 70%);
  bottom: -15%; right: 5%;
  animation: orb-drift-2 20s ease-in-out infinite;
}

@keyframes orb-drift-1 {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(60px, 30px); }
}

@keyframes orb-drift-2 {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(-40px, -20px); }
}

.terms-title {
  font-size: 28px;
  font-weight: 700;
  color: #fff;
  margin: 0;
  position: relative;
  z-index: 1;
}

.terms-content,
.terms-empty {
  max-width: 800px;
  margin: 32px auto;
  padding: 0 16px;
}

.terms-card {
  background: var(--main-bg-color);
  border-radius: var(--main-radius);
  padding: 32px 40px;
  box-shadow: 0 2px 12px var(--main-shadow);
  line-height: 1.8;
  color: var(--main-color);
  font-size: 14px;
}

.terms-card :deep(h1),
.terms-card :deep(h2),
.terms-card :deep(h3) {
  margin: 24px 0 12px;
  font-weight: 600;
}

.terms-card :deep(h1) { font-size: 20px; }
.terms-card :deep(h2) { font-size: 17px; }
.terms-card :deep(h3) { font-size: 15px; }

.terms-card :deep(p) {
  margin: 0 0 12px;
}

.terms-card :deep(ul),
.terms-card :deep(ol) {
  padding-left: 24px;
  margin: 0 0 12px;
}

.terms-card :deep(a) {
  color: var(--theme-color);
}

@media (max-width: 768px) {
  .terms-title { font-size: 22px; }
  .terms-card { padding: 20px 16px; }
}
</style>
