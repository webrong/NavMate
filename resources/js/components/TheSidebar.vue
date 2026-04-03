<template>
  <aside class="sidebar" :class="{ collapsed: collapsed, 'mobile-open': mobileOpen }">
    <div class="sidebar-inner">
      <ul class="sidebar-list">
        <li v-for="cat in sidebarItems" :key="cat.id" class="sidebar-item">
          <a
            href="#"
            class="sidebar-link"
            :class="{ active: activeId === cat.id }"
            @click.prevent="scrollTo(cat.id)"
          >
            <i :class="'io ' + cat.icon + ' icon-fw'"></i>
            <span class="sidebar-text">{{ cat.name }}</span>
          </a>
        </li>
      </ul>
      <div class="sidebar-bottom">
        <button class="sidebar-collapse-btn" @click="$emit('toggle-collapse')">
          <svg :class="{ rotated: collapsed }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6" />
          </svg>
          <span class="sidebar-text">{{ collapsed ? '展开' : '收起' }}</span>
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useCategoryStore } from '../stores/categories';

const props = defineProps({
  mobileOpen: { type: Boolean, default: false },
  collapsed: { type: Boolean, default: false },
});
const emit = defineEmits(['close-mobile', 'toggle-collapse']);

const store = useCategoryStore();
const activeId = ref(null);

const sidebarItems = computed(() => store.sidebarItems);

function scrollTo(catId) {
  activeId.value = catId;
  const el = document.getElementById('section-' + catId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  emit('close-mobile');
}

// Observe sections for active state
let observer = null;

function setupObserver() {
  if (observer) observer.disconnect();
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const id = entry.target.id.replace('section-', '');
          activeId.value = Number(id);
        }
      });
    },
    { rootMargin: '-60px 0px -60% 0px', threshold: 0 }
  );
  document.querySelectorAll('[id^="section-"]').forEach(el => observer.observe(el));
}

onMounted(() => {
  watch(() => store.categories.length, () => {
    setTimeout(setupObserver, 100);
  }, { immediate: true });
});

onUnmounted(() => {
  if (observer) observer.disconnect();
});
</script>
