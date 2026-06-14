<template>
  <a-config-provider :theme="themeConfig" :locale="zhCN">
    <router-view />
  </a-config-provider>
</template>

<script setup>
import { computed } from 'vue';
import { theme as antdTheme } from 'antdv-next';
import zhCN from 'antdv-next/dist/locale/zh_CN';
import { useAdminTheme } from './composables/useAdminTheme';

const { isDark } = useAdminTheme();

const themeConfig = computed(() => ({
  token: {
    colorPrimary: '#fc7c3c',
    colorLink: '#fc7c3c',
    colorSuccess: '#52c41a',
    colorWarning: '#faad14',
    colorError: '#f5222d',
    colorInfo: '#722ed1',
    borderRadius: 8,
    borderRadiusLG: 8,
    borderRadiusSM: 4,
    fontFamily:
      "'Geist', -apple-system, 'PingFang SC', 'Microsoft YaHei', sans-serif",
    fontSize: 14,
  },
  algorithm: isDark.value
    ? antdTheme.darkAlgorithm
    : antdTheme.defaultAlgorithm,
}));
</script>
