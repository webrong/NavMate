<template>
  <div>
    <div class="page-toolbar">
      <a-input-search v-model:value="keyword" placeholder="搜索用户名/邮箱" style="width: 250px" @search="handleSearch" allow-clear />
    </div>

    <a-card :bordered="false">
      <a-table :dataSource="store.items" :columns="columns" :loading="store.loading" :pagination="pagination" @change="handleTableChange" row-key="id" size="middle">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'is_admin'">
            <a-popconfirm title="确认切换管理员权限？" @confirm="toggleAdmin(record)">
              <a-switch v-model:checked="record.is_admin" :disabled="record.id === currentUserId" />
            </a-popconfirm>
          </template>
          <template v-if="column.key === 'created_at'">
            {{ formatDate(record.created_at) }}
          </template>
          <template v-if="column.key === 'actions'">
            <a-popconfirm v-if="record.id !== currentUserId" title="确定删除此用户？" @confirm="handleDelete(record.id)">
              <a-button type="link" danger size="small">删除</a-button>
            </a-popconfirm>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { message } from 'antdv-next';
import { useAdminUsersStore } from '../stores/adminUsers';
import { useAdminAuthStore } from '../stores/adminAuth';

const store = useAdminUsersStore();
const authStore = useAdminAuthStore();
const keyword = ref('');

const currentUserId = computed(() => authStore.user?.id);

const columns = [
  { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
  { title: '用户名', dataIndex: 'name', key: 'name' },
  { title: '邮箱', dataIndex: 'email', key: 'email' },
  { title: '管理员', dataIndex: 'is_admin', key: 'is_admin', width: 80 },
  { title: '注册时间', dataIndex: 'created_at', key: 'created_at', width: 120 },
  { title: '操作', key: 'actions', width: 100 },
];

const pagination = computed(() => ({
  total: store.total,
  current: store.currentFilters.page,
  pageSize: store.currentFilters.limit,
  showTotal: (total) => `共 ${total} 条`,
}));

onMounted(() => {
  store.fetchList();
});

function handleSearch(val) {
  store.fetchList({ keyword: val, page: 1 });
}

function handleTableChange(pag) {
  store.fetchList({ page: pag.current, limit: pag.pageSize });
}

async function toggleAdmin(record) {
  try {
    await store.update(record.id, { is_admin: record.is_admin });
    message.success('更新成功');
    store.fetchList();
  } catch {
    message.error('操作失败');
  }
}

async function handleDelete(id) {
  try {
    await store.remove(id);
    message.success('删除成功');
    store.fetchList();
  } catch {
    message.error('删除失败');
  }
}

function formatDate(date) {
  if (!date) return '';
  return new Date(date).toLocaleDateString('zh-CN');
}
</script>

<style scoped>
.page-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 16px;
}
</style>
