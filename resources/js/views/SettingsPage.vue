<template>
  <div class="content-area">
    <div class="content-section">
      <div class="section-header">
        <h2 class="section-title">布局设置</h2>
        <button class="search-clear-btn" @click="resetLayout">恢复默认</button>
        <button class="search-clear-btn" style="margin-left:4px" @click="saveLayout">保存</button>
      </div>
      <div v-if="loading" class="loading-state">加载中...</div>
      <div v-else class="settings-list">
        <div
          v-for="(cat, index) in categories"
          :key="cat.id"
          class="settings-item"
        >
          <div class="settings-item-controls">
            <button :disabled="index === 0" @click="moveUp(index)" title="上移">&#9650;</button>
            <button :disabled="index === categories.length - 1" @click="moveDown(index)" title="下移">&#9660;</button>
          </div>
          <span class="settings-item-name">{{ cat.name }}</span>
          <label class="settings-toggle">
            <input type="checkbox" v-model="cat.visible" />
            <span>{{ cat.visible ? '显示' : '隐藏' }}</span>
          </label>
        </div>
      </div>
      <div v-if="saved" style="color:var(--theme-color);font-size:13px;margin-top:8px;text-align:center">已保存</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCategoryStore } from '../stores/categories';
import { useLayoutStore } from '../stores/layout';

const categoryStore = useCategoryStore();
const layoutStore = useLayoutStore();
const categories = ref([]);
const loading = ref(true);
const saved = ref(false);

onMounted(async () => {
  await layoutStore.fetchLayout();
  const layoutMap = {};
  layoutStore.data.forEach((item) => {
    layoutMap[item.category_id] = item;
  });

  categories.value = categoryStore.categories.map((cat, index) => ({
    id: cat.id,
    name: cat.name,
    visible: layoutMap[cat.id] ? layoutMap[cat.id].visible : true,
    sort_order: layoutMap[cat.id] ? layoutMap[cat.id].sort_order : index,
  }));

  categories.value.sort((a, b) => a.sort_order - b.sort_order);
  loading.value = false;
});

function moveUp(index) {
  if (index <= 0) return;
  const list = categories.value;
  [list[index - 1], list[index]] = [list[index], list[index - 1]];
}

function moveDown(index) {
  if (index >= categories.value.length - 1) return;
  const list = categories.value;
  [list[index], list[index + 1]] = [list[index + 1], list[index]];
}

async function saveLayout() {
  const layoutData = categories.value.map((cat, index) => ({
    category_id: cat.id,
    visible: cat.visible,
    sort_order: index,
  }));
  await layoutStore.saveLayout(layoutData);
  saved.value = true;
  setTimeout(() => { saved.value = false; }, 2000);
}

function resetLayout() {
  categories.value.forEach((cat, index) => {
    cat.visible = true;
    cat.sort_order = index;
  });
}
</script>

<style scoped>
.settings-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.settings-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 6px;
  transition: background 0.15s;
}

.settings-item:hover {
  background: rgba(0, 0, 0, 0.03);
}

.settings-item-controls {
  display: flex;
  gap: 2px;
}

.settings-item-controls button {
  width: 24px;
  height: 24px;
  border: none;
  background: none;
  cursor: pointer;
  color: var(--muted-color);
  border-radius: 4px;
  font-size: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.settings-item-controls button:hover:not(:disabled) {
  background: rgba(0, 0, 0, 0.06);
  color: var(--main-color);
}

.settings-item-controls button:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.settings-item-name {
  flex: 1;
  font-size: 14px;
  color: var(--main-color);
}

.settings-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  font-size: 13px;
  color: var(--muted-color);
}

.settings-toggle input {
  cursor: pointer;
}
</style>
