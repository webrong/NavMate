<template>
  <div>
    <div class="page-toolbar">
      <a-space>
        <a-input-search v-model:value="keyword" placeholder="搜索站点名称/URL" style="width: 250px" @search="handleSearch" allow-clear />
        <a-select v-model:value="filterCategory" placeholder="全部分类" style="width: 120px" allow-clear @change="onCategoryChange">
          <a-select-option v-for="cat in store.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</a-select-option>
        </a-select>
        <a-select v-model:value="filterPublic" placeholder="公开状态" style="width: 120px" allow-clear @change="onPublicChange">
          <a-select-option value="">全部</a-select-option>
          <a-select-option :value="1">公开</a-select-option>
          <a-select-option :value="0">私有</a-select-option>
        </a-select>
      </a-space>
      <a-button type="primary" @click="openCreate"><PlusOutlined /> 新增站点</a-button>
    </div>

    <a-card :bordered="false">
      <a-table :dataSource="store.items" :columns="columns" :loading="store.loading" :pagination="pagination" @change="handleTableChange" row-key="id" size="middle">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'title'">
            <div style="display:flex;align-items:center;gap:8px">
              <img v-if="record.favicon_url" :src="record.favicon_url" alt style="width:16px;height:16px;border-radius:2px" />
              <span>{{ record.title }}</span>
            </div>
          </template>
          <template v-if="column.key === 'url'">
            <a :href="record.url" target="_blank" rel="noopener" class="site-url">{{ record.url }}</a>
          </template>
          <template v-if="column.key === 'category'">
            {{ record.category?.name || '-' }}
          </template>
          <template v-if="column.key === 'is_public'">
            <a-tag :color="record.is_public ? 'blue' : 'orange'">{{ record.is_public ? '公开' : '私有' }}</a-tag>
          </template>
          <template v-if="column.key === 'is_active'">
            <a-tag :color="record.is_active ? 'green' : 'red'">{{ record.is_active ? '启用' : '禁用' }}</a-tag>
          </template>
          <template v-if="column.key === 'actions'">
            <a-space>
              <a-button type="link" size="small" @click="openEdit(record)">编辑</a-button>
              <a-popconfirm title="确定删除此站点？" @confirm="handleDelete(record.id)">
                <a-button type="link" danger size="small">删除</a-button>
              </a-popconfirm>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>

    <a-modal v-model:open="modalVisible" :title="editingId ? '编辑站点' : '新增站点'" @ok="handleSubmit" :confirm-loading="submitting" width="600px">
      <a-form :model="form" layout="vertical" style="margin-top:16px">
        <a-form-item label="URL" required>
          <a-input-search v-model:value="form.url" placeholder="https://example.com" enter-button="抓取信息" :search="handleFetchUrl" :loading="fetchingUrl" />
        </a-form-item>
        <a-form-item label="标题" required>
          <a-input v-model:value="form.title" placeholder="站点名称" />
        </a-form-item>
        <a-form-item label="分类" required>
          <a-select v-model:value="form.category_id" placeholder="选择分类">
            <a-select-option v-for="cat in store.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="Favicon URL">
          <a-input v-model:value="form.favicon_url" placeholder="自动抓取或手动填写" />
        </a-form-item>
        <a-form-item label="描述">
          <a-textarea v-model:value="form.description" placeholder="站点描述（可选）" :rows="2" />
        </a-form-item>
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="排序">
              <a-input-number v-model:value="form.sort_order" :min="0" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="状态">
              <a-switch v-model:checked="form.is_active" checked-children="启用" un-checked-children="禁用" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="公开">
              <a-switch v-model:checked="form.is_public" checked-children="公开" un-checked-children="私有" />
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { message } from 'antdv-next';
import { PlusOutlined } from '@ant-design/icons-vue';
import { useAdminSitesStore } from '../stores/adminSites';

const store = useAdminSitesStore();
const keyword = ref('');
const filterCategory = ref(undefined);
const filterPublic = ref(undefined);
const modalVisible = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const fetchingUrl = ref(false);

const form = reactive({
  url: '',
  title: '',
  category_id: null,
  favicon_url: '',
  description: '',
  is_public: true,
  is_active: true,
  sort_order: 0,
});

const columns = [
  { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
  { title: '站点', key: 'title', ellipsis: true },
  { title: 'URL', dataIndex: 'url', key: 'url', ellipsis: true },
  { title: '分类', key: 'category' },
  { title: '类型', key: 'is_public', width: 80 },
  { title: '状态', key: 'is_active', width: 80 },
  { title: '点击量', dataIndex: 'clicks', key: 'clicks', width: 80 },
  { title: '操作', key: 'actions', width: 140 },
];

const pagination = computed(() => ({
  total: store.total,
  current: store.currentFilters.page,
  pageSize: store.currentFilters.limit,
  showTotal: (total) => `共 ${total} 条`,
  showSizeChanger: true,
}));

onMounted(() => {
  store.fetchList();
  store.fetchCategories();
});

function handleSearch() {
  store.fetchList({ keyword: keyword.value, category_id: filterCategory.value, is_public: filterPublic.value, page: 1 });
}

function onPublicChange(val) {
  store.fetchList({ is_public: val, page: 1 });
}
function onCategoryChange(val) {
  store.fetchList({ category_id: val, page: 1 });
}
function handleTableChange(pag) {
  store.fetchList({ page: pag.current, limit: pag.pageSize });
}

function openCreate() {
  editingId.value = null;
  Object.assign(form, { url: '', title: '', category_id: null, favicon_url: '', description: '', is_public: true, is_active: true, sort_order: 0 });
  modalVisible.value = true;
}

function openEdit(record) {
  editingId.value = record.id;
  Object.assign(form, {
    url: record.url,
    title: record.title,
    category_id: record.category_id,
    favicon_url: record.favicon_url || '',
    description: record.description || '',
    is_public: record.is_public,
    is_active: record.is_active,
    sort_order: record.sort_order || 0,
  });
  modalVisible.value = true;
}

async function handleFetchUrl() {
  if (!form.url) { message.warning('请先输入URL'); return; }
  fetchingUrl.value = true;
  try {
    const { data } = await store.fetchUrl(form.url);
    if (data.title) form.title = data.title;
    if (data.favicon_url) form.favicon_url = data.favicon_url;
    message.success('抓取成功');
  } catch {
    message.error('抓取失败');
  } finally {
    fetchingUrl.value = false;
  }
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
    store.fetchList();
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
    store.fetchList();
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
  gap: 12px;
}
.site-url {
  color: #1677ff;
  font-size: 12px;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
