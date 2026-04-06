<template>
  <div v-if="authStore.isAuthenticated" class="content-section quick-links-section" @contextmenu.prevent>
    <div class="section-header">
      <h2 class="section-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        我的快捷
      </h2>
      <span class="section-count">{{ links.length }}</span>
      <div style="flex:1"></div>
      <button class="quick-add-btn" @click="showAdd = true" title="添加链接">+ 添加</button>
    </div>
    <div class="site-grid">
      <a
        v-for="link in links"
        :key="link.id"
        :href="link.url"
        target="_blank"
        rel="noopener noreferrer"
        class="site-card"
        @contextmenu.prevent="openContext($event, link)"
        @touchstart.prevent="onTouchStart($event, link)"
        @touchend="onTouchEnd"
        @touchmove="onTouchEnd"
      >
        <button class="site-fav" @click.stop.prevent="remove(link.id)" title="删除">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="site-favicon">
          <img v-if="link.favicon_url && !imgErrors[link.id]" :src="link.favicon_url" :alt="link.title" loading="lazy" @error="imgErrors[link.id] = true" />
          <span v-else class="site-favicon-letter">{{ link.title ? link.title.charAt(0).toUpperCase() : '?' }}</span>
        </div>
        <div class="site-info">
          <span class="site-name">{{ link.title }}</span>
          <span class="site-desc" v-if="link.description">{{ link.description }}</span>
        </div>
        <span class="site-goto">&#8599;</span>
      </a>
      <div v-if="links.length === 0" class="section-empty" style="padding:20px">还没有快捷链接，点击上方 + 添加你的常用网址</div>
    </div>

    <!-- Context Menu -->
    <Teleport to="body">
      <div v-if="ctx.show" class="ctx-overlay" @click="closeContext" @contextmenu.prevent="closeContext">
        <ul class="ctx-menu" :style="{ left: ctx.x + 'px', top: ctx.y + 'px' }">
          <li class="ctx-item" @click="editLink">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            编辑
          </li>
          <li class="ctx-sep"></li>
          <li class="ctx-item ctx-danger" @click="deleteLink">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            删除
          </li>
        </ul>
      </div>
    </Teleport>

    <!-- Add Modal -->
    <Teleport to="body">
      <div v-if="showAdd" class="auth-modal-overlay" @click.self="showAdd = false">
        <div class="auth-modal">
          <button class="auth-modal-close" @click="showAdd = false">&times;</button>
          <h3 class="auth-modal-title">添加快捷链接</h3>
          <form @submit.prevent="submit">
            <div class="auth-field">
              <label>网址 *</label>
              <input v-model="newUrl" type="url" placeholder="https://example.com" required />
            </div>
            <div class="auth-field">
              <label>标题（留空自动获取）</label>
              <input v-model="newTitle" type="text" placeholder="自动获取网页标题" />
            </div>
            <div v-if="addError" class="auth-error">{{ addError }}</div>
            <button type="submit" class="auth-submit" :disabled="adding">
              {{ adding ? '添加中...' : '添加' }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="showEdit" class="auth-modal-overlay" @click.self="showEdit = false">
        <div class="auth-modal">
          <button class="auth-modal-close" @click="showEdit = false">&times;</button>
          <h3 class="auth-modal-title">编辑快捷链接</h3>
          <form @submit.prevent="saveEdit">
            <div class="auth-field">
              <label>网址</label>
              <input :value="editUrl" type="url" readonly class="edit-url-readonly" />
            </div>
            <div class="edit-fetch-row">
              <div class="edit-favicon-preview">
                <img v-if="editFavicon" :src="editFavicon" alt="favicon" />
                <span v-else class="site-favicon-letter">{{ editTitle ? editTitle.charAt(0).toUpperCase() : '?' }}</span>
              </div>
              <button type="button" class="edit-fetch-btn" :disabled="fetching" @click="fetchMeta">
                {{ fetching ? '获取中...' : '获取' }}
              </button>
            </div>
            <div class="auth-field">
              <label>标题 *</label>
              <input v-model="editTitle" type="text" placeholder="链接标题" required />
            </div>
            <div class="auth-field">
              <label>描述</label>
              <input v-model="editDesc" type="text" placeholder="可选描述" />
            </div>
            <div v-if="editError" class="auth-error">{{ editError }}</div>
            <button type="submit" class="auth-submit" :disabled="saving">
              {{ saving ? '保存中...' : '保存' }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import request from '../utils/request';
import { useAuthStore } from '../stores/auth';
import { useUserLinksStore } from '../stores/userLinks';

const authStore = useAuthStore();
const store = useUserLinksStore();
const links = computed(() => store.links);
const imgErrors = reactive({});

// Add
const showAdd = ref(false);
const newUrl = ref('');
const newTitle = ref('');
const addError = ref('');
const adding = ref(false);

// Context menu
const ctx = reactive({ show: false, x: 0, y: 0, link: null });
let longPressTimer = null;

function openContext(e, link) {
  ctx.x = Math.min(e.clientX, window.innerWidth - 160);
  ctx.y = Math.min(e.clientY, window.innerHeight - 100);
  ctx.link = link;
  ctx.show = true;
}

function closeContext() {
  ctx.show = false;
}

function onTouchStart(e, link) {
  longPressTimer = setTimeout(() => {
    const touch = e.touches[0];
    openContext({ clientX: touch.clientX, clientY: touch.clientY }, link);
  }, 500);
}

function onTouchEnd() {
  clearTimeout(longPressTimer);
}

function editLink() {
  if (!ctx.link) return;
  editUrl.value = ctx.link.url || '';
  editTitle.value = ctx.link.title || '';
  editDesc.value = ctx.link.description || '';
  editFavicon.value = ctx.link.favicon_url || '';
  editTarget = ctx.link;
  ctx.show = false;
  showEdit.value = true;
}

function deleteLink() {
  if (!ctx.link) return;
  store.removeLink(ctx.link.id);
  ctx.show = false;
}

// Edit
const showEdit = ref(false);
const editUrl = ref('');
const editTitle = ref('');
const editDesc = ref('');
const editFavicon = ref('');
const editError = ref('');
const saving = ref(false);
const fetching = ref(false);
let editTarget = null;

async function fetchMeta() {
  editError.value = '';
  fetching.value = true;
  try {
    const { data } = await request.post('/api/fetch-url', { url: editUrl.value });
    if (data.title) editTitle.value = data.title;
    if (data.favicon_url) editFavicon.value = data.favicon_url;
  } catch {
    editError.value = '获取失败，请检查网址是否可访问';
  } finally {
    fetching.value = false;
  }
}

async function saveEdit() {
  editError.value = '';
  if (!editTitle.value.trim()) {
    editError.value = '标题不能为空';
    return;
  }
  saving.value = true;
  try {
    await store.updateLink(editTarget.id, {
      title: editTitle.value.trim(),
      description: editDesc.value.trim() || null,
      favicon_url: editFavicon.value || null,
    });
    showEdit.value = false;
  } catch (e) {
    editError.value = e.response?.data?.message || '保存失败';
  } finally {
    saving.value = false;
  }
}

// Add submit
async function submit() {
  addError.value = '';
  if (!newUrl.value.trim()) {
    addError.value = '请输入网址';
    return;
  }
  adding.value = true;
  try {
    await store.addLink(newUrl.value.trim(), newTitle.value.trim() || null);
    newUrl.value = '';
    newTitle.value = '';
    showAdd.value = false;
  } catch (e) {
    addError.value = e.response?.data?.message || '添加失败';
  } finally {
    adding.value = false;
  }
}

function remove(id) {
  store.removeLink(id);
}

function onKeydown(e) {
  if (e.key === 'Escape') {
    if (ctx.show) closeContext();
    else if (showEdit.value) showEdit.value = false;
    else if (showAdd.value) showAdd.value = false;
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<style scoped>
.quick-links-section {
  border: 1px dashed rgba(var(--theme-color-rgb), 0.3);
}

.quick-add-btn {
  padding: 4px 12px;
  font-size: 12px;
  color: var(--theme-color);
  background: rgba(var(--theme-color-rgb), 0.08);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s;
}

.quick-add-btn:hover {
  background: rgba(var(--theme-color-rgb), 0.16);
}

.section-count {
  font-size: 12px;
  color: var(--muted-color);
  margin-left: 2px;
}

.edit-url-readonly {
  background: rgba(var(--theme-color-rgb), 0.04);
  color: var(--muted-color);
  cursor: default;
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.edit-fetch-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.edit-favicon-preview {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  overflow: hidden;
  background: rgba(var(--theme-color-rgb), 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.edit-favicon-preview img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.edit-fetch-btn {
  padding: 6px 16px;
  font-size: 13px;
  color: var(--theme-color);
  background: rgba(var(--theme-color-rgb), 0.08);
  border: 1px solid rgba(var(--theme-color-rgb), 0.2);
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s;
}

.edit-fetch-btn:hover:not(:disabled) {
  background: rgba(var(--theme-color-rgb), 0.16);
}

.edit-fetch-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>

<style>
/* Context menu (unscoped for Teleport) */
.ctx-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
}

.ctx-menu {
  position: fixed;
  margin: 0;
  padding: 4px 0;
  list-style: none;
  background: var(--main-bg-color);
  border: 1px solid rgba(var(--theme-color-rgb), 0.12);
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  min-width: 120px;
  font-size: 13px;
}

.ctx-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  color: var(--main-color);
  cursor: pointer;
  transition: background 0.12s;
}

.ctx-item:hover {
  background: rgba(var(--theme-color-rgb), 0.08);
}

.ctx-danger {
  color: #e74c3c;
}

.ctx-danger:hover {
  background: rgba(231, 76, 60, 0.08);
}

.ctx-sep {
  height: 1px;
  margin: 4px 8px;
  background: rgba(var(--theme-color-rgb), 0.1);
}
</style>
