<template>
  <a-layout class="admin-layout">
    <!-- Desktop sidebar -->
    <a-layout-sider
      v-if="!isMobile"
      v-model:collapsed="collapsed"
      :trigger="null"
      collapsible
      :width="220"
      :collapsed-width="72"
      class="admin-sider"
    >
      <div class="admin-logo">
        <div class="logo-icon"><GlobalOutlined /></div>
        <transition name="fade">
          <span v-if="!collapsed" class="logo-text">NavMate</span>
        </transition>
      </div>

      <div class="admin-menu-wrapper">
        <div class="menu-group">
          <div v-if="!collapsed" class="menu-group-label">概览</div>
          <a-menu v-model:selectedKeys="selectedKeys" mode="inline" class="admin-menu" @click="onMenuClick">
            <a-menu-item key="/admin/dashboard"><DashboardOutlined /><span>仪表盘</span></a-menu-item>
          </a-menu>
        </div>
        <div class="menu-group">
          <div v-if="!collapsed" class="menu-group-label">内容管理</div>
          <a-menu v-model:selectedKeys="selectedKeys" mode="inline" class="admin-menu" @click="onMenuClick">
            <a-menu-item key="/admin/categories"><AppstoreOutlined /><span>分类管理</span></a-menu-item>
            <a-menu-item key="/admin/sites"><GlobalOutlined /><span>站点管理</span></a-menu-item>
            <a-menu-item key="/admin/bookmarks"><ImportOutlined /><span>书签导入</span></a-menu-item>
            <a-menu-item key="/admin/ads"><PictureOutlined /><span>广告管理</span></a-menu-item>
          </a-menu>
        </div>
        <div class="menu-group">
          <div v-if="!collapsed" class="menu-group-label">系统管理</div>
          <a-menu v-model:selectedKeys="selectedKeys" mode="inline" class="admin-menu" @click="onMenuClick">
            <a-menu-item key="/admin/users"><TeamOutlined /><span>用户管理</span></a-menu-item>
            <a-menu-item key="/admin/analytics"><BarChartOutlined /><span>数据统计</span></a-menu-item>
            <a-menu-item key="/admin/settings"><SettingOutlined /><span>系统设置</span></a-menu-item>
            <a-menu-item key="/admin/system/monitor"><MonitorOutlined /><span>系统监控</span></a-menu-item>
            <a-menu-item key="/admin/system/upgrade"><CloudUploadOutlined /><span>系统升级</span></a-menu-item>
          </a-menu>
        </div>
      </div>
    </a-layout-sider>

    <!-- Mobile drawer sidebar -->
    <a-drawer
      v-if="isMobile"
      :open="drawerOpen"
      placement="left"
      :width="220"
      :closable="false"
      :body-style="{ padding: 0, background: 'var(--admin-sidebar)' }"
      @close="drawerOpen = false"
    >
      <div class="admin-logo">
        <div class="logo-icon"><GlobalOutlined /></div>
        <span class="logo-text">NavMate</span>
      </div>
      <div class="admin-menu-wrapper">
        <div class="menu-group">
          <div class="menu-group-label">概览</div>
          <a-menu v-model:selectedKeys="selectedKeys" mode="inline" class="admin-menu" @click="onMobileMenuClick">
            <a-menu-item key="/admin/dashboard"><DashboardOutlined /><span>仪表盘</span></a-menu-item>
          </a-menu>
        </div>
        <div class="menu-group">
          <div class="menu-group-label">内容管理</div>
          <a-menu v-model:selectedKeys="selectedKeys" mode="inline" class="admin-menu" @click="onMobileMenuClick">
            <a-menu-item key="/admin/categories"><AppstoreOutlined /><span>分类管理</span></a-menu-item>
            <a-menu-item key="/admin/sites"><GlobalOutlined /><span>站点管理</span></a-menu-item>
            <a-menu-item key="/admin/bookmarks"><ImportOutlined /><span>书签导入</span></a-menu-item>
            <a-menu-item key="/admin/ads"><PictureOutlined /><span>广告管理</span></a-menu-item>
          </a-menu>
        </div>
        <div class="menu-group">
          <div class="menu-group-label">系统管理</div>
          <a-menu v-model:selectedKeys="selectedKeys" mode="inline" class="admin-menu" @click="onMobileMenuClick">
            <a-menu-item key="/admin/users"><TeamOutlined /><span>用户管理</span></a-menu-item>
            <a-menu-item key="/admin/analytics"><BarChartOutlined /><span>数据统计</span></a-menu-item>
            <a-menu-item key="/admin/settings"><SettingOutlined /><span>系统设置</span></a-menu-item>
            <a-menu-item key="/admin/system/monitor"><MonitorOutlined /><span>系统监控</span></a-menu-item>
            <a-menu-item key="/admin/system/upgrade"><CloudUploadOutlined /><span>系统升级</span></a-menu-item>
          </a-menu>
        </div>
      </div>
    </a-drawer>

    <a-layout class="admin-main-layout">
      <div class="admin-header">
        <div class="header-left">
          <component
            :is="isMobile ? MenuUnfoldOutlined : (collapsed ? MenuUnfoldOutlined : MenuFoldOutlined)"
            class="trigger"
            @click="toggleSidebar"
          />
          <a-breadcrumb class="admin-breadcrumb">
            <a-breadcrumb-item v-if="currentGroup">{{ currentGroup }}</a-breadcrumb-item>
            <a-breadcrumb-item>{{ currentTitle }}</a-breadcrumb-item>
          </a-breadcrumb>
        </div>
        <div class="header-right">
          <a-tooltip :title="isDark ? '切换到亮色模式' : '切换到深色模式'">
            <component
              :is="isDark ? BulbFilled : BulbOutlined"
              class="header-icon-btn"
              @click="toggleDark"
            />
          </a-tooltip>
          <a-dropdown>
            <div class="user-info">
              <a-avatar size="small" class="user-avatar">
                <template #icon><UserOutlined /></template>
              </a-avatar>
              <span class="user-name">{{ authStore.userName }}</span>
            </div>
            <template #overlay>
              <a-menu>
                <a-menu-item key="home" @click="goHome">
                  <HomeOutlined /><span style="margin-left: 8px">返回前台</span>
                </a-menu-item>
                <a-menu-divider />
                <a-menu-item key="logout" @click="handleLogout">
                  <LogoutOutlined /><span style="margin-left: 8px">退出登录</span>
                </a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </div>
      </div>

      <div class="admin-content">
        <router-view v-slot="{ Component }">
          <transition name="admin-page-fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </a-layout>
  </a-layout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core';
import { useAdminAuthStore } from '../stores/adminAuth';
import { useAdminTheme } from '../composables/useAdminTheme';
import {
  DashboardOutlined, AppstoreOutlined, GlobalOutlined, TeamOutlined,
  BarChartOutlined, ImportOutlined, PictureOutlined, SettingOutlined,
  MonitorOutlined, CloudUploadOutlined, MenuFoldOutlined, MenuUnfoldOutlined,
  UserOutlined, LogoutOutlined, HomeOutlined, BulbOutlined, BulbFilled,
} from '@ant-design/icons-vue';

const router = useRouter();
const route = useRoute();
const authStore = useAdminAuthStore();
const { isDark, toggleDark } = useAdminTheme();

const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smaller('lg');

const collapsed = ref(false);
const drawerOpen = ref(false);
const selectedKeys = ref([route.path]);

const groupMap = {
  '/admin/dashboard': '概览',
  '/admin/categories': '内容管理', '/admin/sites': '内容管理',
  '/admin/bookmarks': '内容管理', '/admin/ads': '内容管理',
  '/admin/users': '系统管理', '/admin/analytics': '系统管理',
  '/admin/settings': '系统管理', '/admin/system/monitor': '系统管理',
  '/admin/system/upgrade': '系统管理',
};

const currentTitle = computed(() => route.meta?.title || '仪表盘');
const currentGroup = computed(() => groupMap[route.path] || '');

watch(() => route.path, (path) => { selectedKeys.value = [path]; });

function toggleSidebar() {
  if (isMobile.value) { drawerOpen.value = !drawerOpen.value; }
  else { collapsed.value = !collapsed.value; }
}

function onMenuClick({ key }) {
  if (route.path !== key) router.push(key).catch(() => {});
}

function onMobileMenuClick({ key }) {
  drawerOpen.value = false;
  if (route.path !== key) router.push(key).catch(() => {});
}

function goHome() { window.open('/', '_blank'); }

async function handleLogout() {
  await authStore.logout();
  router.replace('/admin/login').catch(() => {});
}
</script>

<style scoped>
.admin-layout { min-height: 100vh; background: var(--admin-background); }

.admin-sider {
  background: var(--admin-sidebar) !important;
  border-right: 1px solid var(--admin-sidebar-border);
  transition: width 0.2s ease, flex 0.2s ease;
  position: sticky; top: 0; height: 100vh; overflow: hidden;
}
:deep(.admin-sider .ant-layout-sider-children) { display: flex; flex-direction: column; }

.admin-logo {
  height: 64px; display: flex; align-items: center; gap: 12px;
  padding: 0 20px; border-bottom: 1px solid var(--admin-sidebar-border); flex-shrink: 0;
}
.logo-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, #fc7c3c, #e33636);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 18px; flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(252, 124, 60, 0.3);
}
.logo-text { font-size: 18px; font-weight: 700; color: var(--admin-sidebar-foreground); white-space: nowrap; }

.admin-menu-wrapper { flex: 1; overflow-y: auto; padding: 8px 0; }
.menu-group { margin-bottom: 4px; }
.menu-group-label {
  padding: 12px 24px 4px; font-size: 11px; font-weight: 600;
  color: var(--admin-muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;
}

:deep(.admin-menu) { background: transparent !important; border-inline-end: none !important; }
:deep(.admin-menu .ant-menu-item) {
  margin: 2px 8px; border-radius: var(--admin-radius-md);
  color: var(--admin-sidebar-foreground); width: calc(100% - 16px);
}
:deep(.admin-menu .ant-menu-item:hover) { background: var(--admin-muted) !important; }
:deep(.admin-menu .ant-menu-item-selected) {
  background: var(--admin-sidebar-accent) !important;
  color: var(--admin-sidebar-accent-foreground) !important;
}
:deep(.admin-menu .ant-menu-item-selected::after) { display: none; }

.admin-main-layout { background: var(--admin-background); }

.admin-header {
  height: 64px; background: var(--admin-header-bg);
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--admin-border);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 24px; position: sticky; top: 0; z-index: 50;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.trigger {
  font-size: 18px; cursor: pointer; color: var(--admin-foreground);
  padding: 8px; border-radius: var(--admin-radius-md); transition: all 0.2s;
}
.trigger:hover { background: var(--admin-muted); color: var(--admin-primary); }
.admin-breadcrumb { font-size: 14px; }
:deep(.admin-breadcrumb a), :deep(.admin-breadcrumb span) { color: var(--admin-muted-foreground) !important; }
:deep(.admin-breadcrumb li:last-child span) { color: var(--admin-foreground) !important; font-weight: 600; }

.header-right { display: flex; align-items: center; gap: 16px; }
.header-icon-btn {
  font-size: 18px; cursor: pointer; color: var(--admin-foreground);
  padding: 8px; border-radius: var(--admin-radius-md); transition: all 0.2s;
}
.header-icon-btn:hover { background: var(--admin-muted); color: var(--admin-primary); }

.user-info {
  display: flex; align-items: center; gap: 8px; cursor: pointer;
  padding: 4px 8px; border-radius: var(--admin-radius-md); transition: background 0.2s;
}
.user-info:hover { background: var(--admin-muted); }
.user-avatar { background: var(--admin-primary) !important; }
.user-name { font-size: 14px; color: var(--admin-foreground); white-space: nowrap; }

.admin-content { padding: 24px; min-height: calc(100vh - 64px); }

@media (max-width: 768px) {
  .admin-content { padding: 16px; }
  .user-name { display: none; }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
