<template>
  <div class="dashboard">
    <!-- Stat cards: 3 columns × 2 rows -->
    <a-row :gutter="[16, 16]" class="stat-row">
      <a-col :xs="24" :sm="12" :lg="8" v-for="item in statCards" :key="item.label">
        <div class="stat-card admin-card admin-card-hover">
          <div class="stat-card-body">
            <div class="stat-info">
              <div class="stat-label">{{ item.label }}</div>
              <div class="stat-value">{{ formatNum(item.value) }}</div>
            </div>
            <div :class="['icon-chip', item.chipClass]">
              <component :is="item.icon" />
            </div>
          </div>
          <div v-if="item.trend !== undefined" class="stat-trend" :class="item.trend >= 0 ? 'trend-up' : 'trend-down'">
            <component :is="item.trend >= 0 ? ArrowUpOutlined : ArrowDownOutlined" />
            <span>{{ Math.abs(item.trend) }}%</span>
            <span class="trend-hint">{{ item.trendHint || '较昨日' }}</span>
          </div>
        </div>
      </a-col>
    </a-row>

    <!-- Loading skeleton for stat cards -->
    <a-row v-if="dashboardStore.loading" :gutter="[16, 16]" class="stat-row">
      <a-col :xs="24" :sm="12" :lg="8" v-for="n in 6" :key="n">
        <a-skeleton active :paragraph="{ rows: 1 }" class="stat-skeleton" />
      </a-col>
    </a-row>

    <!-- Two-column tables -->
    <a-row :gutter="[16, 16]" class="table-row">
      <a-col :xs="24" :lg="12">
        <div class="admin-card table-card">
          <div class="table-card-header">
            <span class="table-card-title">
              <FireOutlined style="color: var(--admin-primary)" /> 热门站点 Top 10
            </span>
          </div>
          <a-table
            :data-source="dashboardStore.topSites"
            :columns="siteColumns"
            :pagination="false"
            size="middle"
            row-key="id"
            :loading="dashboardStore.loading"
            :locale="{ emptyText: '暂无数据' }"
          >
            <template #bodyCell="{ column, record, index }">
              <template v-if="column.key === 'rank'">
                <span :class="['rank-badge', index < 3 ? 'rank-top' : '']">{{ index + 1 }}</span>
              </template>
              <template v-else-if="column.key === 'title'">
                <div class="site-cell">
                  <img
                    v-if="record.favicon_url"
                    :src="record.favicon_url"
                    class="site-favicon"
                    alt=""
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    @error="onFaviconError"
                  />
                  <span>{{ record.title }}</span>
                </div>
              </template>
              <template v-else-if="column.key === 'clicks'">
                <span class="clicks-value">{{ record.clicks }}</span>
              </template>
            </template>
          </a-table>
        </div>
      </a-col>

      <a-col :xs="24" :lg="12">
        <div class="admin-card table-card">
          <div class="table-card-header">
            <span class="table-card-title">
              <ClockCircleOutlined style="color: var(--admin-primary)" /> 最近添加
            </span>
          </div>
          <a-table
            :data-source="dashboardStore.recentSites"
            :columns="recentColumns"
            :pagination="false"
            size="middle"
            row-key="id"
            :loading="dashboardStore.loading"
            :locale="{ emptyText: '暂无数据' }"
          >
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'title'">
                <div class="site-cell">
                  <img
                    v-if="record.favicon_url"
                    :src="record.favicon_url"
                    class="site-favicon"
                    alt=""
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    @error="onFaviconError"
                  />
                  <span>{{ record.title }}</span>
                </div>
              </template>
              <template v-else-if="column.key === 'created_at'">
                <span class="time-cell">{{ formatDate(record.created_at) }}</span>
              </template>
            </template>
          </a-table>
        </div>
      </a-col>
    </a-row>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';
import { useAdminDashboardStore } from '../stores/adminDashboard';
import {
  AppstoreOutlined, GlobalOutlined, EyeOutlined, LockOutlined,
  ThunderboltOutlined, RiseOutlined, FireOutlined, ClockCircleOutlined,
  ArrowUpOutlined, ArrowDownOutlined,
} from '@ant-design/icons-vue';

const dashboardStore = useAdminDashboardStore();

onMounted(() => {
  dashboardStore.fetchData();
});

const statCards = computed(() => {
  const s = dashboardStore.stats || {};
  return [
    { label: '分类总数', value: s.categories || 0, chipClass: 'icon-chip--info', icon: AppstoreOutlined },
    { label: '站点总数', value: s.sites || 0, chipClass: 'icon-chip--primary', icon: GlobalOutlined },
    { label: '公开站点', value: s.public_sites || 0, chipClass: 'icon-chip--success', icon: EyeOutlined },
    { label: '私有站点', value: s.private_sites || 0, chipClass: 'icon-chip--warning', icon: LockOutlined },
    { label: '总点击量', value: s.total_clicks || 0, chipClass: 'icon-chip--danger', icon: ThunderboltOutlined },
    {
      label: '今日点击', value: s.today_clicks || 0,
      chipClass: 'icon-chip--cyan', icon: RiseOutlined,
      trend: typeof s.click_growth === 'number' ? Math.round(s.click_growth * 100) / 100 : undefined,
      trendHint: '较昨日',
    },
  ];
});

const siteColumns = [
  { title: '#', key: 'rank', width: 50 },
  { title: '站点', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '点击量', dataIndex: 'clicks', key: 'clicks', width: 100, align: 'right' },
];

const recentColumns = [
  { title: '站点', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '添加时间', dataIndex: 'created_at', key: 'created_at', width: 120 },
];

function formatNum(n) {
  if (n === undefined || n === null) return '0';
  return Number(n).toLocaleString('zh-CN');
}

// Replace broken favicons with a colored letter tile instead of hiding the
// <img> entirely. Hiding leaves an empty gap; replacing keeps the table row
// aligned and avoids a re-render storm when many favicons 404 at once.
// We swap the <img> for a <span> with the site's first character and clear
// the original src so the browser never retries it.
function onFaviconError(e) {
  const img = e.target;
  if (!img || !img.parentNode) return;
  const title = img.getAttribute('alt') || img.dataset.title || '';
  const letter = (title.trim()[0] || '?').toUpperCase();
  const span = document.createElement('span');
  span.className = 'site-favicon site-favicon--fallback';
  span.textContent = letter;
  img.parentNode.replaceChild(span, img);
}

function formatDate(date) {
  if (!date) return '';
  const d = new Date(date);
  const now = new Date();
  const diff = (now - d) / 1000;
  if (diff < 60) return '刚刚';
  if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
  if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
  if (diff < 2592000) return Math.floor(diff / 86400) + '天前';
  return d.toLocaleDateString('zh-CN');
}
</script>

<style scoped>
.dashboard { width: 100%; }

/* ---- Stat cards ---- */
.stat-card {
  padding: 22px 24px;
  cursor: default;
}

.stat-card-body {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 30px;
  font-weight: 700;
  line-height: 1.15;
  color: var(--admin-card-foreground);
  letter-spacing: -0.02em;
  margin-top: 6px;
}

.stat-label {
  font-size: 13px;
  color: var(--admin-muted-foreground);
  font-weight: 500;
}

.stat-trend {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 14px;
  padding: 3px 8px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 12px;
}
.trend-up { color: var(--admin-success); background: rgba(82, 196, 26, 0.1); }
.trend-down { color: var(--admin-destructive); background: rgba(245, 34, 45, 0.1); }
.trend-hint { color: var(--admin-muted-foreground); font-weight: 400; margin-left: 4px; }

/* Make stat cards' icon chips slightly smaller and rounded-lg */
.stat-card .icon-chip {
  width: 48px;
  height: 48px;
  font-size: 22px;
  border-radius: var(--admin-radius);
  flex-shrink: 0;
}

.stat-skeleton {
  padding: 20px;
  background: var(--admin-card);
  border-radius: var(--admin-radius);
  box-shadow: var(--admin-shadow-card);
}

/* ---- Table cards ---- */
.table-row { margin-top: 16px; }

.table-card {
  overflow: hidden;
}

.table-card-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--admin-border-light);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.table-card-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--admin-card-foreground);
  display: flex;
  align-items: center;
  gap: 8px;
  letter-spacing: -0.01em;
}

:deep(.table-card .ant-table) {
  background: transparent;
}

:deep(.table-card .ant-table-thead > tr > th) {
  background: transparent;
  font-weight: 600;
  color: var(--admin-muted-foreground);
  border-bottom: 1px solid var(--admin-border-light);
}

:deep(.table-card .ant-table-tbody > tr:last-child > td) {
  border-bottom: none;
}

:deep(.table-card .ant-table-tbody > tr > td) {
  border-bottom: 1px solid var(--admin-border-light);
}

:deep(.table-card .ant-table-tbody > tr:hover > td) {
  background: var(--admin-muted) !important;
}

/* Rank badge */
.rank-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
  background: var(--admin-muted);
  color: var(--admin-muted-foreground);
}
.rank-badge.rank-top {
  background: linear-gradient(135deg, #fc7c3c, #e33636);
  color: #fff;
}

/* Site cell */
.site-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.site-favicon {
  width: 16px;
  height: 16px;
  border-radius: 3px;
  flex-shrink: 0;
}
/* Letter tile shown when a favicon 404s — keeps the row aligned instead of
   leaving a gap. Sits on a soft brand-tinted background. */
.site-favicon--fallback {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #fc7c3c, #e33636);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  line-height: 1;
}

.clicks-value {
  font-weight: 600;
  color: var(--admin-primary);
}

.time-cell {
  color: var(--admin-muted-foreground);
  font-size: 13px;
}
</style>
