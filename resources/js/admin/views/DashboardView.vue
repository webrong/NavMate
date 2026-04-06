<template>
  <div>
    <a-row :gutter="16" class="stat-row">
      <a-col :span="4" v-for="item in statCards" :key="item.label">
        <a-card>
          <a-statistic :title="item.label" :value="item.value" :value-style="{ color: item.color }">
            <template #prefix><component :is="item.icon" /></template>
          </a-statistic>
        </a-card>
      </a-col>
    </a-row>

    <a-row :gutter="16" style="margin-top: 16px">
      <a-col :span="12">
        <a-card title="热门站点 Top 10" :bordered="false" size="small">
          <a-table :dataSource="dashboardStore.topSites" :columns="siteColumns" :pagination="false" size="small" row-key="id" :loading="dashboardStore.loading">
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'clicks'">
                <a-tag color="blue">{{ record.clicks }}</a-tag>
              </template>
            </template>
          </a-table>
        </a-card>
      </a-col>
      <a-col :span="12">
        <a-card title="最近添加" :bordered="false" size="small">
          <a-table :dataSource="dashboardStore.recentSites" :columns="recentColumns" :pagination="false" size="small" row-key="id" :loading="dashboardStore.loading">
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'created_at'">
                {{ formatDate(record.created_at) }}
              </template>
            </template>
          </a-table>
        </a-card>
      </a-col>
    </a-row>
  </div>
</template>

<script setup>
import { onMounted, computed, h } from 'vue';
import { useAdminDashboardStore } from '../stores/adminDashboard';
import { AppstoreOutlined, GlobalOutlined, EyeOutlined, LinkOutlined, TeamOutlined, ThunderboltOutlined } from '@ant-design/icons-vue';

const dashboardStore = useAdminDashboardStore();

onMounted(() => {
  dashboardStore.fetchData();
});

const statCards = computed(() => {
  const s = dashboardStore.stats || {};
  return [
    { label: '分类数', value: s.categories || 0, color: '#1677ff', icon: AppstoreOutlined },
    { label: '站点数', value: s.sites || 0, color: '#52c41a', icon: GlobalOutlined },
    { label: '公开站点', value: s.public_sites || 0, color: '#13c2c2', icon: EyeOutlined },
    { label: '私有站点', value: s.private_sites || 0, color: '#faad14', icon: LinkOutlined },
    { label: '总点击量', value: s.total_clicks || 0, color: '#722ed1', icon: TeamOutlined },
    { label: '今日点击', value: s.today_clicks || 0, color: '#f5222d', icon: ThunderboltOutlined },
  ];
});

const siteColumns = [
  { title: '站点', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '点击量', dataIndex: 'clicks', key: 'clicks', width: 80 },
];

const recentColumns = [
  { title: '站点', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '添加时间', dataIndex: 'created_at', key: 'created_at', width: 120 },
];

function formatDate(date) {
  if (!date) return '';
  return new Date(date).toLocaleDateString('zh-CN');
}
</script>

<style scoped>
.stat-row .ant-card {
  border-radius: 8px;
}
</style>
