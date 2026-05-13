<template>
  <a-layout style="min-height: 100vh">
    <a-layout-sider v-model:collapsed="collapsed" collapsible :trigger="null" width="220" theme="dark">
      <div class="admin-logo">
        <span v-if="!collapsed" class="logo-text">{{ siteName }}</span>
        <span v-else class="logo-text-short">Nav</span>
      </div>
      <a-menu v-model:selectedKeys="selectedKeys" v-model:openKeys="openKeys" theme="dark" mode="inline" @click="onMenuClick">
        <a-menu-item key="/admin/dashboard">
          <DashboardOutlined />
          <span>仪表盘</span>
        </a-menu-item>
        <a-menu-item key="/admin/categories">
          <AppstoreOutlined />
          <span>分类管理</span>
        </a-menu-item>
        <a-menu-item key="/admin/sites">
          <GlobalOutlined />
          <span>站点管理</span>
        </a-menu-item>
        <a-menu-item key="/admin/users">
          <TeamOutlined />
          <span>用户管理</span>
        </a-menu-item>
        <a-menu-item key="/admin/analytics">
          <BarChartOutlined />
          <span>数据统计</span>
        </a-menu-item>
        <a-menu-item key="/admin/bookmarks">
          <ImportOutlined />
          <span>书签导入</span>
        </a-menu-item>
        <a-menu-item key="/admin/ads">
          <PictureOutlined />
          <span>广告管理</span>
        </a-menu-item>
        <a-menu-item key="/admin/settings">
          <SettingOutlined />
          <span>系统设置</span>
        </a-menu-item>
        <a-menu-item key="/admin/system/monitor">
          <MonitorOutlined />
          <span>系统监控</span>
        </a-menu-item>
        <a-menu-item key="/admin/system/upgrade">
          <CloudUploadOutlined />
          <span>系统升级</span>
        </a-menu-item>
      </a-menu>
    </a-layout-sider>

    <a-layout>
      <a-layout-header class="admin-header">
        <div class="header-left">
          <MenuFoldOutlined v-if="!collapsed" class="trigger" @click="collapsed = true" />
          <MenuUnfoldOutlined v-else class="trigger" @click="collapsed = false" />
          <span class="page-title">{{ currentTitle }}</span>
        </div>
        <div class="header-right">
          <a-dropdown>
            <span class="user-info">
              <UserOutlined style="margin-right:6px" />
              {{ authStore.userName }}
            </span>
            <template #overlay>
              <a-menu>
                <a-menu-item @click="goHome">
                  <HomeOutlined /> 返回前台
                </a-menu-item>
                <a-menu-divider />
                <a-menu-item @click="handleLogout">
                  <LogoutOutlined /> 退出登录
                </a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </div>
      </a-layout-header>

      <a-layout-content class="admin-content">
        <router-view />
      </a-layout-content>
    </a-layout>
  </a-layout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAdminAuthStore } from '../stores/adminAuth';
import {
  DashboardOutlined,
  AppstoreOutlined,
  GlobalOutlined,
  TeamOutlined,
  BarChartOutlined,
  ImportOutlined,
  PictureOutlined,
  SettingOutlined,
  InfoCircleOutlined,
  MonitorOutlined,
  CloudUploadOutlined,
  MenuFoldOutlined,
  MenuUnfoldOutlined,
  UserOutlined,
  LogoutOutlined,
  HomeOutlined,
} from '@ant-design/icons-vue';

const router = useRouter();
const route = useRoute();
const authStore = useAdminAuthStore();

const collapsed = ref(false);
const selectedKeys = ref([route.path]);
const openKeys = ref([]);

const siteName = '导航后台';
const currentTitle = computed(() => route.meta?.title || '仪表盘');

watch(() => route.path, (path) => {
  selectedKeys.value = [path];
});

function onMenuClick({ key }) {
  if (route.path !== key) {
    router.push(key).catch(() => {});
  }
}

function goHome() {
  window.open('/', '_blank');
}

async function handleLogout() {
  await authStore.logout();
  router.replace('/admin/login').catch(() => {});
}
</script>

<style scoped>
.admin-logo {
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 18px;
  font-weight: 600;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}

.logo-text-short {
  font-size: 16px;
}

.admin-header {
  background: #fff;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  height: 48px;
  line-height: 48px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.trigger {
  font-size: 18px;
  cursor: pointer;
  transition: color 0.2s;
}

.trigger:hover {
  color: #1677ff;
}

.page-title {
  font-size: 15px;
  font-weight: 500;
  color: #333;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  cursor: pointer;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: #555;
}

.admin-content {
  margin: 16px;
  padding: 20px;
  background: #f5f5f5;
  border-radius: 8px;
  min-height: calc(100vh - 48px - 32px);
}
</style>
