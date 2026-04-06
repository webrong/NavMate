<template>
  <header class="main-header">
    <nav class="header-nav">
      <div class="header-inner">
        <a href="/" class="logo-link">
          <img :src="'/static/image/logo.png'" alt="导航" class="logo-img" />
        </a>
        <div class="header-links d-none d-md-flex">
          <a href="/" class="header-link active">
            <span>首页</span>
          </a>
        </div>
        <div class="flex-fill"></div>

        <!-- Quick Tools -->
        <div class="quick-tools d-none d-md-flex">
          <div
            v-for="group in toolGroups" :key="group.label"
            class="tool-group"
            @mouseenter="openTool(group.label)"
            @mouseleave="scheduleClose"
          >
            <span class="tool-trigger" :class="{ active: activeTool === group.label }">
              {{ group.label }}
              <svg class="tool-arrow" viewBox="0 0 12 12" width="10" height="10"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </span>
            <Transition name="dropdown">
              <div v-if="activeTool === group.label" class="tool-dropdown">
                <a
                  v-for="item in group.items" :key="item.name"
                  :href="item.url"
                  target="_blank"
                  rel="noopener"
                  class="tool-dropdown-item"
                >{{ item.name }}</a>
              </div>
            </Transition>
          </div>
        </div>

        <div class="header-actions">
          <!-- User area -->
          <div v-if="authStore.initialized" class="tool-group">
            <template v-if="authStore.isAuthenticated">
              <div class="user-avatar" @click.stop="showUserMenu = !showUserMenu">
                <img v-if="authStore.avatarUrl" :src="authStore.avatarUrl" alt="头像" class="user-avatar-img" />
                <template v-else>{{ authStore.userName ? authStore.userName.charAt(0).toUpperCase() : 'U' }}</template>
              </div>
              <Transition name="dropdown">
                <div v-if="showUserMenu" class="user-dropdown">
                  <div class="user-dropdown-header">{{ authStore.userName }}</div>
                  <router-link to="/profile" class="user-dropdown-item" @click="showUserMenu = false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    个人资料
                  </router-link>
                  <router-link to="/favorites" class="user-dropdown-item" @click="showUserMenu = false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    我的收藏
                  </router-link>
                  <router-link to="/settings" class="user-dropdown-item" @click="showUserMenu = false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                    布局设置
                  </router-link>
                  <div class="user-dropdown-divider"></div>
                  <div class="user-dropdown-item" @click="logout">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    退出登录
                  </div>
                </div>
              </Transition>
            </template>
            <template v-else>
              <button class="header-link" @click="openLogin" style="border:none;background:none;cursor:pointer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span style="margin-left:4px">登录</span>
              </button>
            </template>
          </div>

          <button class="mobile-menu-btn d-md-none" @click="$emit('toggle-mobile-menu')" aria-label="菜单">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="6" x2="21" y2="6" />
              <line x1="3" y1="12" x2="21" y2="12" />
              <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
          </button>
        </div>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { toolGroups } from '../config/tools';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';

defineEmits(['toggle-mobile-menu']);

const router = useRouter();
const authStore = useAuthStore();
const toast = useToastStore();
const activeTool = ref(null);
const showUserMenu = ref(false);
let closeTimer = null;

function openTool(label) {
  clearTimeout(closeTimer);
  activeTool.value = label;
}

function scheduleClose() {
  closeTimer = setTimeout(() => {
    activeTool.value = null;
  }, 150);
}

function openLogin() {
  router.push({ query: { login: 'true' } });
}

function logout() {
  showUserMenu.value = false;
  authStore.logout();
  toast.success('已退出登录');
}

function onGlobalKeydown(e) {
  if (e.key === 'Escape') {
    if (activeTool.value) { activeTool.value = null; e.stopPropagation(); }
    if (showUserMenu.value) { showUserMenu.value = false; e.stopPropagation(); }
  }
}

onMounted(() => document.addEventListener('keydown', onGlobalKeydown));
onUnmounted(() => document.removeEventListener('keydown', onGlobalKeydown));
</script>

<style scoped>
.header-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.quick-tools {
  display: flex;
  align-items: center;
  gap: 2px;
}

.tool-group {
  position: relative;
}

.tool-trigger {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 6px 8px;
  font-size: 13px;
  border-radius: 6px;
  cursor: pointer;
  color: var(--muted-color2);
  transition: all 0.2s;
  white-space: nowrap;
}

.tool-trigger:hover,
.tool-trigger.active {
  background: var(--hover-bg);
  color: var(--main-color);
}

.tool-arrow {
  transition: transform 0.2s;
}

.tool-trigger.active .tool-arrow {
  transform: rotate(180deg);
}

.tool-dropdown {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  min-width: 120px;
  background: var(--main-bg-color);
  border-radius: 10px;
  box-shadow: 0 8px 24px var(--main-shadow);
  border: 1px solid var(--border-color);
  padding: 6px;
  z-index: 1001;
}

.tool-dropdown-item {
  display: block;
  padding: 7px 12px;
  font-size: 13px;
  color: var(--main-color);
  border-radius: 6px;
  transition: background 0.15s;
  white-space: nowrap;
}

.tool-dropdown-item:hover {
  background: var(--hover-bg);
  color: var(--theme-color);
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-4px);
}

.user-avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}
</style>
