<template>
  <div class="page-toolbar admin-card">
    <div class="toolbar-left">
      <slot name="left" />
    </div>
    <div class="toolbar-right">
      <slot name="right" />
    </div>
  </div>
</template>

<script setup>
/**
 * Standardized page toolbar for CRUD views.
 *
 * Usage:
 *   <PageToolbar>
 *     <template #left>
 *       <a-input-search ... />
 *       <a-select ... />
 *     </template>
 *     <template #right>
 *       <a-button type="primary">新增</a-button>
 *     </template>
 *   </PageToolbar>
 */
</script>

<style scoped>
.page-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  margin-bottom: 16px;
  gap: 12px;
  flex-wrap: wrap;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

@media (max-width: 768px) {
  .page-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .toolbar-left,
  .toolbar-right {
    width: 100%;
  }
}
</style>
