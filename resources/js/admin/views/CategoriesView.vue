<template>
  <div>
    <PageToolbar>
      <template #left>
        <a-input-search v-model:value="keyword" placeholder="搜索分类名称" style="width: 250px" @search="handleSearch" allow-clear />
      </template>
      <template #right>
        <a-button type="primary" @click="openCreate">
          <PlusOutlined /> 新增分类
        </a-button>
      </template>
    </PageToolbar>

    <div class="admin-card">
      <a-table
        :dataSource="store.items"
        :columns="columns"
        :loading="store.loading"
        :pagination="false"
        :defaultExpandAllRows="true"
        row-key="id"
        size="middle"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'name'">
            <i v-if="record.icon" :class="'io ' + record.icon" style="margin-right: 6px; font-size: 16px"></i>
            <span style="font-weight: 500">{{ record.name }}</span>
          </template>
          <template v-if="column.key === 'sites_count'">
            <a-tag>{{ record.sites_count ?? 0 }}</a-tag>
          </template>
          <template v-if="column.key === 'is_active'">
            <a-tag :color="record.is_active ? 'green' : 'red'">{{ record.is_active ? '启用' : '禁用' }}</a-tag>
          </template>
          <template v-if="column.key === 'actions'">
            <a-space>
              <a-tooltip title="编辑">
                <a-button type="text" size="small" @click="openEdit(record)"><EditOutlined /></a-button>
              </a-tooltip>
              <a-popconfirm title="确定删除此分类？" @confirm="handleDelete(record.id)">
                <a-tooltip title="删除">
                  <a-button type="text" danger size="small"><DeleteOutlined /></a-button>
                </a-tooltip>
              </a-popconfirm>
            </a-space>
          </template>
        </template>
      </a-table>
    </div>

    <!-- Create/Edit Modal -->
    <a-modal v-model:open="modalVisible" :title="editingId ? '编辑分类' : '新增分类'" @ok="handleSubmit" :confirm-loading="submitting" width="520px">
      <a-form :model="form" layout="vertical" style="margin-top: 16px">
        <a-form-item label="分类名称" required>
          <a-input v-model:value="form.name" placeholder="请输入分类名称" @change="onNameChange" />
        </a-form-item>
        <a-form-item label="Slug" required>
          <a-input v-model:value="form.slug" placeholder="URL 友好标识符，如 ai-tools" />
        </a-form-item>
        <a-form-item label="描述">
          <a-textarea v-model:value="form.description" placeholder="分类描述（可选）" :rows="2" />
        </a-form-item>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="图标">
              <a-input v-model:value="form.icon" placeholder="Emoji 或图标类名" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="排序">
              <a-input-number v-model:value="form.sort_order" :min="0" style="width: 100%" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-form-item label="上级分类">
          <a-tree-select
            v-model:value="form.parent_id"
            :tree-data="parentTreeOptions"
            placeholder="无（顶级分类）"
            allow-clear
            tree-default-expand-all
            :field-names="{ label: 'name', value: 'id', children: 'children' }"
          />
        </a-form-item>
        <a-form-item label="状态">
          <a-switch v-model:checked="form.is_active" checked-children="启用" un-checked-children="禁用" />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { message } from 'antdv-next';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import PageToolbar from '../components/PageToolbar.vue';
import { useAdminCategoriesStore } from '../stores/adminCategories';

const store = useAdminCategoriesStore();
const keyword = ref('');
const modalVisible = ref(false);
const editingId = ref(null);
const submitting = ref(false);

const form = reactive({
  name: '',
  slug: '',
  description: '',
  icon: '',
  sort_order: 0,
  is_active: true,
  parent_id: undefined,
});

const columns = [
  { title: '名称', key: 'name', dataIndex: 'name' },
  { title: 'Slug', dataIndex: 'slug', key: 'slug', ellipsis: true },
  { title: '站点数', key: 'sites_count', width: 80 },
  { title: '排序', dataIndex: 'sort_order', key: 'sort_order', width: 70 },
  { title: '状态', dataIndex: 'is_active', key: 'is_active', width: 80 },
  { title: '操作', key: 'actions', width: 140 },
];

// Build tree options for TreeSelect, excluding current editing item and its descendants
const parentTreeOptions = computed(() => {
  const excludeIds = getDescendantIds(editingId.value);
  return filterTree(store.items, excludeIds);
});

function getDescendantIds(id) {
  if (!id) return new Set();
  const ids = new Set([id]);
  function collect(items) {
    for (const item of items) {
      if (ids.has(item.parent_id)) {
        ids.add(item.id);
      }
      if (item.children) collect(item.children);
    }
  }
  collect(store.items);
  return ids;
}

function filterTree(items, excludeIds) {
  return items
    .filter(item => !excludeIds.has(item.id))
    .map(item => ({
      ...item,
      children: item.children ? filterTree(item.children, excludeIds) : undefined,
    }));
}

// Auto-generate slug from name (only on create)
let slugManuallyEdited = false;

function onNameChange() {
  if (!editingId.value && !slugManuallyEdited && form.name) {
    form.slug = form.name
      .toLowerCase()
      .replace(/[\s]+/g, '-')
      .replace(/[^\w\-\u4e00-\u9fa5]/g, '')
      .substring(0, 60);
  }
}

onMounted(() => {
  store.fetchList();
});

function handleSearch(val) {
  store.fetchList({ keyword: val });
}

function openCreate() {
  editingId.value = null;
  slugManuallyEdited = false;
  Object.assign(form, { name: '', slug: '', description: '', icon: '', sort_order: 0, is_active: true, parent_id: undefined });
  modalVisible.value = true;
}

function openEdit(record) {
  editingId.value = record.id;
  slugManuallyEdited = true;
  Object.assign(form, {
    name: record.name,
    slug: record.slug,
    description: record.description || '',
    icon: record.icon || '',
    sort_order: record.sort_order || 0,
    is_active: record.is_active,
    parent_id: record.parent_id ?? undefined,
  });
  modalVisible.value = true;
}

async function handleSubmit() {
  submitting.value = true;
  try {
    if (editingId.value) {
      await store.update(editingId.value, { ...form });
      message.success('更新成功');
    } else {
      await store.create({ ...form });
      message.success('添加成功');
    }
    modalVisible.value = false;
    store.fetchList(keyword.value ? { keyword: keyword.value } : {});
  } catch (e) {
    message.error(e.response?.data?.message || '操作失败');
  } finally {
    submitting.value = false;
  }
}

async function handleDelete(id) {
  try {
    await store.remove(id);
    message.success('删除成功');
    store.fetchList(keyword.value ? { keyword: keyword.value } : {});
  } catch (e) {
    message.error(e.response?.data?.message || '删除失败');
  }
}
</script>

<style scoped>
.page-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
</style>
