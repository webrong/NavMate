<template>
  <div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px">
      <a-space>
        <a-select v-model:value="filterPosition" style="width: 140px" allow-clear placeholder="全部位置" :getPopupContainer="trigger => trigger.parentNode" @change="filterPosition = $event || ''">
          <a-select-option value="content_between">内容区间</a-select-option>
          <a-select-option value="sidebar_bottom">侧边栏底部</a-select-option>
          <a-select-option value="footer_above">页脚上方</a-select-option>
        </a-select>
        <a-select v-model:value="filterStatus" style="width: 120px" allow-clear placeholder="全部状态" @change="filterStatus = $event || ''">
          <a-select-option :value="true">启用</a-select-option>
          <a-select-option :value="false">禁用</a-select-option>
        </a-select>
      </a-space>
      <a-button type="primary" @click="openCreate">
        <template #icon><PlusOutlined /></template>
        新增广告
      </a-button>
    </div>

    <a-card :bordered="false">
      <a-table :data-source="filteredAds" :columns="columns" :pagination="false" row-key="id" size="middle">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'image_url'">
            <img v-if="record.image_url" :src="record.image_url" style="max-height: 40px; max-width: 120px; border-radius: 4px; object-fit: cover" />
          </template>
          <template v-if="column.key === 'position'">
            <a-tag :color="positionColors[record.position]">{{ positionLabels[record.position] }}</a-tag>
          </template>
          <template v-if="column.key === 'is_active'">
            <a-tag :color="record.is_active ? 'green' : 'default'">{{ record.is_active ? '启用' : '禁用' }}</a-tag>
          </template>
          <template v-if="column.key === 'actions'">
            <a-space>
              <a @click="openEdit(record)">编辑</a>
              <a-popconfirm title="确认删除？" @confirm="handleDelete(record.id)">
                <a style="color: #ff4d4f">删除</a>
              </a-popconfirm>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>

    <a-modal v-model:open="modalVisible" :title="editingId ? '编辑广告' : '新增广告'" @ok="handleSubmit" :confirm-loading="submitting" width="560px">
      <a-form :model="form" layout="vertical" style="margin-top: 16px">
        <a-form-item label="广告标题" required>
          <a-input v-model:value="form.title" placeholder="广告展示标题" />
        </a-form-item>
        <a-form-item label="广告图片" required>
          <div style="display: flex; align-items: flex-start; gap: 8px">
            <a-input v-model:value="form.image_url" placeholder="图片 URL" style="flex: 1" />
            <a-upload :show-upload-list="false" accept="image/*" :before-upload="handleImageUpload">
              <a-button :loading="imageUploading">上传</a-button>
            </a-upload>
          </div>
          <div v-if="form.image_url" style="margin-top: 8px">
            <img :src="form.image_url" alt="预览" style="max-height: 120px; border-radius: 6px; border: 1px solid #eee" />
          </div>
        </a-form-item>
        <a-form-item label="链接地址" required>
          <a-input v-model:value="form.link_url" placeholder="https://example.com" />
        </a-form-item>
        <a-form-item label="投放位置" required>
          <a-radio-group v-model:value="form.position">
            <a-radio value="content_between">内容区间</a-radio>
            <a-radio value="sidebar_bottom">侧边栏底部</a-radio>
            <a-radio value="footer_above">页脚上方</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="排序">
              <a-input-number v-model:value="form.sort_order" :min="0" style="width: 100%" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="状态">
              <a-switch v-model:checked="form.is_active" checked-children="启用" un-checked-children="禁用" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="打开方式">
              <a-select v-model:value="form.target" :getPopupContainer="trigger => trigger.parentNode">
                <a-select-option value="_blank">新窗口</a-select-option>
                <a-select-option value="_self">当前窗口</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { message } from 'antdv-next';
import { PlusOutlined } from '@ant-design/icons-vue';
import request from '../utils/request';

const ads = ref([]);
const modalVisible = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const imageUploading = ref(false);
const filterPosition = ref('');
const filterStatus = ref('');

const positionLabels = { content_between: '内容区间', sidebar_bottom: '侧边栏底部', footer_above: '页脚上方' };
const positionColors = { content_between: 'blue', sidebar_bottom: 'green', footer_above: 'orange' };
const positionOptions = [
  { value: 'content_between', label: '内容区间' },
  { value: 'sidebar_bottom', label: '侧边栏底部' },
  { value: 'footer_above', label: '页脚上方' },
];

const form = reactive({
  title: '',
  image_url: '',
  link_url: '',
  position: 'content_between',
  sort_order: 0,
  is_active: true,
  target: '_blank',
});

const columns = [
  { title: '图片', key: 'image_url', width: 140 },
  { title: '标题', dataIndex: 'title', key: 'title' },
  { title: '位置', key: 'position', width: 120 },
  { title: '状态', key: 'is_active', width: 80 },
  { title: '排序', dataIndex: 'sort_order', key: 'sort_order', width: 70 },
  { title: '操作', key: 'actions', width: 120 },
];

const filteredAds = computed(() => {
  return ads.value.filter(ad => {
    if (filterPosition.value && ad.position !== filterPosition.value) return false;
    if (filterStatus.value !== '' && filterStatus.value !== undefined && ad.is_active !== filterStatus.value) return false;
    return true;
  });
});

onMounted(fetchAds);

async function fetchAds() {
  try {
    const { data } = await request.get('/admin/api/ads');
    ads.value = data.data || [];
  } catch {
    message.error('加载广告列表失败');
  }
}

function openCreate() {
  editingId.value = null;
  Object.assign(form, { title: '', image_url: '', link_url: '', position: 'content_between', sort_order: 0, is_active: true, target: '_blank' });
  modalVisible.value = true;
}

function openEdit(record) {
  editingId.value = record.id;
  Object.assign(form, { ...record });
  modalVisible.value = true;
}

async function handleSubmit() {
  if (!form.title || !form.image_url || !form.link_url || !form.position) {
    message.warning('请填写必填项');
    return;
  }
  submitting.value = true;
  try {
    if (editingId.value) {
      await request.put(`/admin/api/ads/${editingId.value}`, { ...form });
      message.success('更新成功');
    } else {
      await request.post('/admin/api/ads', { ...form });
      message.success('添加成功');
    }
    modalVisible.value = false;
    fetchAds();
  } catch (e) {
    message.error(e.response?.data?.message || '操作失败');
  } finally {
    submitting.value = false;
  }
}

async function handleDelete(id) {
  try {
    await request.delete(`/admin/api/ads/${id}`);
    message.success('删除成功');
    fetchAds();
  } catch (e) {
    message.error(e.response?.data?.message || '删除失败');
  }
}

async function handleImageUpload(file) {
  imageUploading.value = true;
  try {
    const formData = new FormData();
    formData.append('image', file);
    const { data } = await request.post('/admin/api/ads/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (data.code === 0) {
      form.image_url = data.data.url;
      message.success('上传成功');
    }
  } catch (e) {
    message.error(e.response?.data?.message || '上传失败');
  } finally {
    imageUploading.value = false;
  }
  return false;
}
</script>
