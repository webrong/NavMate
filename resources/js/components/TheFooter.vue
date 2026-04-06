<template>
  <footer class="main-footer">
    <div class="footer-inner">
      <div class="footer-grid">
        <div class="footer-brand">
          <img :src="siteSettings.settings.site_logo || '/static/image/logo.png'" alt="导航" class="footer-logo" />
          <p class="footer-desc">{{ siteSettings.siteName }} 上班人必备的职场办公导航网站</p>
        </div>
        <div class="footer-links-col">
          <div class="footer-links">
            <router-link to="/terms">用户协议</router-link>
            <router-link to="/terms">免责声明</router-link>
            <router-link to="/about">关于本站</router-link>
            <router-link to="/about">广告合作</router-link>
            <router-link to="/about">收录投稿</router-link>
            <a v-for="link in friendLinks" :key="link.id" :href="link.url" target="_blank" rel="noopener">{{ link.name }}</a>
          </div>
        </div>
        <div class="footer-qrcodes" v-if="siteSettings.settings.qrcode_1_image || siteSettings.settings.qrcode_2_image">
          <div class="footer-qrcode" v-if="siteSettings.settings.qrcode_1_image">
            <div class="qrcode-box">
              <img :src="siteSettings.settings.qrcode_1_image" :alt="siteSettings.settings.qrcode_1_label || '二维码'" />
            </div>
            <span class="qrcode-label">{{ siteSettings.settings.qrcode_1_label || '扫码关注' }}</span>
          </div>
          <div class="footer-qrcode" v-if="siteSettings.settings.qrcode_2_image">
            <div class="qrcode-box">
              <img :src="siteSettings.settings.qrcode_2_image" :alt="siteSettings.settings.qrcode_2_label || '二维码'" />
            </div>
            <span class="qrcode-label">{{ siteSettings.settings.qrcode_2_label || '扫码关注' }}</span>
          </div>
        </div>
      </div>
      <div class="footer-copyright">
        <div v-if="siteSettings.settings.footer_text" v-html="sanitizedFooter"></div>
        <template v-else>Copyright &copy; {{ new Date().getFullYear() }} {{ siteSettings.siteName }}</template>
        <template v-if="siteSettings.settings.icp_number"> | <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener" style="color: inherit">{{ siteSettings.settings.icp_number }}</a></template>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useSiteSettingsStore } from '../stores/siteSettings';
import request from '../utils/request';
import { sanitizeHtml } from '../composables/useSanitize';

const siteSettings = useSiteSettingsStore();
const friendLinks = ref([]);

const sanitizedFooter = computed(() => sanitizeHtml(siteSettings.settings.footer_text));

onMounted(async () => {
  try {
    const { data } = await request.get('/api/friend-links');
    friendLinks.value = data || [];
  } catch {
    // ignore
  }
});
</script>
