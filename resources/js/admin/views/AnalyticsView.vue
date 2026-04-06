<template>
  <div :class="{ 'dashboard-fullscreen': isFullscreen }">
    <!-- Toolbar -->
    <div class="page-toolbar">
      <h2 v-if="isFullscreen" class="fullscreen-title">数据统计大屏</h2>
      <div v-else></div>
      <a-space>
        <a-range-picker v-model:value="dateRange" :presets="presetRanges" format="YYYY-MM-DD" @change="fetchAll" style="width: 260px" />
        <a-button @click="toggleFullscreen">
          {{ isFullscreen ? '退出大屏' : '全屏模式' }}
        </a-button>
      </a-space>
    </div>

    <!-- Summary cards -->
    <a-row :gutter="16" style="margin-bottom: 16px">
      <a-col :span="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic title="今日点击" :value="summary.today_clicks" :value-style="statStyle">
            <template #prefix><ThunderboltOutlined /></template>
          </a-statistic>
          <div class="stat-sub" v-if="summary.today_clicks > 0">
            <span class="live-dot"></span> 实时更新中
          </div>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic title="总点击量" :value="summary.total_clicks" :value-style="statStyle" />
          <div class="stat-growth" :class="{ positive: summary.click_growth > 0, negative: summary.click_growth < 0 }">
            <span v-if="summary.click_growth > 0">↑</span>
            <span v-else-if="summary.click_growth < 0">↓</span>
            {{ Math.abs(summary.click_growth) }}% 环比
          </div>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic title="独立访客" :value="summary.unique_visitors" :value-style="{ ...statStyle, color: '#52c41a' }" />
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic title="日均点击" :value="summary.avg_daily_clicks" :precision="1" :value-style="{ ...statStyle, color: '#faad14' }" />
        </a-card>
      </a-col>
    </a-row>

    <a-row :gutter="16" style="margin-bottom: 16px">
      <!-- Trends Chart -->
      <a-col :span="12">
        <a-card title="点击趋势" :bordered="false">
          <div v-if="trends.length" class="trend-chart">
            <div class="trend-y-axis">
              <span>{{ maxCount }}</span>
              <span>{{ Math.round(maxCount / 2) }}</span>
              <span>0</span>
            </div>
            <div class="trend-bars">
              <div v-for="item in trends" :key="item.date" class="trend-bar-group">
                <div class="trend-tooltip">{{ item.date }}<br/>{{ item.count }} 次</div>
                <div class="trend-bar" :style="{ height: getBarHeight(item.count) + 'px' }"></div>
                <div class="trend-label">{{ formatShortDate(item.date) }}</div>
              </div>
            </div>
          </div>
          <a-empty v-else description="数据积累中，暂无趋势数据" />
        </a-card>
      </a-col>

      <!-- Hourly Distribution -->
      <a-col :span="12">
        <a-card title="时段分布" :bordered="false">
          <div v-if="hasHourlyData" class="hourly-chart">
            <div class="hourly-bars">
              <div v-for="item in hourlyData" :key="item.hour" class="hourly-bar-group">
                <div class="hourly-tooltip">{{ item.hour }}:00<br/>{{ item.count }} 次</div>
                <div class="hourly-bar" :style="{ height: getHourlyHeight(item.count) + 'px' }"></div>
                <div class="hourly-label">{{ item.hour }}</div>
              </div>
            </div>
          </div>
          <a-empty v-else description="数据积累中，暂无时段数据" />
        </a-card>
      </a-col>
    </a-row>

    <a-row :gutter="16" style="margin-bottom: 16px">
      <!-- Top Categories -->
      <a-col :span="8">
        <a-card title="热门分类" :bordered="false">
          <div v-if="topCategories.length">
            <div v-for="(cat, i) in topCategories" :key="cat.id" class="category-item">
              <div class="category-header">
                <span class="category-rank">{{ i + 1 }}</span>
                <span v-if="cat.icon" style="margin-right: 4px">{{ cat.icon }}</span>
                <span class="category-name">{{ cat.name }}</span>
                <span class="category-count">{{ cat.clicks }}</span>
              </div>
              <div class="category-bar-bg">
                <div class="category-bar" :style="{ width: getCategoryPercent(cat.clicks) + '%' }"></div>
              </div>
            </div>
          </div>
          <a-empty v-else description="数据积累中" />
        </a-card>
      </a-col>

      <!-- Top Sites -->
      <a-col :span="16">
        <a-card title="热门站点" :bordered="false">
          <a-table v-if="topSites.length" :dataSource="topSites" :columns="topColumns" :pagination="false" size="small" row-key="site_id">
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'site'">
                <div style="display: flex; align-items: center; gap: 8px">
                  <img v-if="record.site?.favicon_url" :src="record.site.favicon_url" style="width: 16px; height: 16px; border-radius: 2px" />
                  <div>
                    <div style="font-weight: 500">{{ record.site?.title || '-' }}</div>
                    <div style="font-size: 11px; color: #999">{{ record.site?.url || '' }}</div>
                  </div>
                </div>
              </template>
              <template v-if="column.key === 'clicks'">
                <div style="display: flex; align-items: center; gap: 8px">
                  <div class="clicks-bar-bg">
                    <div class="clicks-bar" :style="{ width: getClicksPercent(record.clicks) + '%' }"></div>
                  </div>
                  <span style="font-weight: 500; min-width: 40px">{{ record.clicks }}</span>
                </div>
              </template>
            </template>
          </a-table>
          <a-empty v-else description="数据积累中" />
        </a-card>
      </a-col>
    </a-row>

    <!-- Recent Clicks -->
    <a-card title="最近点击" :bordered="false">
      <template #extra>
        <a-button size="small" @click="fetchRecentClicks">刷新</a-button>
      </template>
      <a-table v-if="recentClicks.length" :dataSource="recentClicks" :columns="recentColumns" :pagination="false" size="small" row-key="id">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'site_title'">
            <a v-if="record.site_url" :href="record.site_url" target="_blank" rel="noopener">{{ record.site_title }}</a>
            <span v-else>{{ record.site_title }}</span>
          </template>
          <template v-if="column.key === 'ip_address'">
            <span style="font-family: monospace; font-size: 12px">{{ record.ip_address }}</span>
          </template>
          <template v-if="column.key === 'clicked_at'">
            <span style="color: #666; font-size: 12px">{{ formatTime(record.clicked_at) }}</span>
          </template>
        </template>
      </a-table>
      <a-empty v-else description="暂无点击记录" />
    </a-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { ThunderboltOutlined } from '@ant-design/icons-vue';
import request from '../utils/request';

const isFullscreen = ref(false);
const dateRange = ref(null);
const trends = ref([]);
const topSites = ref([]);
const topCategories = ref([]);
const hourlyData = ref([]);
const recentClicks = ref([]);
const summary = reactive({ total_clicks: 0, unique_visitors: 0, avg_daily_clicks: 0, today_clicks: 0, click_growth: 0 });
const loading = ref(false);
let refreshTimer = null;

const statStyle = computed(() => isFullscreen.value
  ? { color: '#00d4ff', fontSize: '32px' }
  : { color: '#1677ff', fontSize: '28px' }
);

const presetRanges = [
  { label: '最近 7 天', value: [(() => { const d = new Date(); d.setDate(d.getDate() - 7); return d; })(), new Date()] },
  { label: '最近 30 天', value: [(() => { const d = new Date(); d.setDate(d.getDate() - 30); return d; })(), new Date()] },
  { label: '最近 90 天', value: [(() => { const d = new Date(); d.setDate(d.getDate() - 90); return d; })(), new Date()] },
];

const topColumns = [
  { title: '排名', key: 'index', width: 50, customRender: ({ index }) => index + 1 },
  { title: '站点', key: 'site' },
  { title: '点击量', key: 'clicks', width: 200 },
];

const recentColumns = [
  { title: '站点', key: 'site_title', ellipsis: true },
  { title: 'IP', key: 'ip_address', width: 140 },
  { title: '时间', key: 'clicked_at', width: 170 },
];

onMounted(() => {
  fetchAll();
  // Auto-refresh today's data every 30s
  refreshTimer = setInterval(() => {
    fetchSummary();
    fetchRecentClicks();
  }, 30000);
});

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer);
  if (isFullscreen.value) document.exitFullscreen?.();
});

function toggleFullscreen() {
  if (!isFullscreen.value) {
    document.documentElement.requestFullscreen?.();
    isFullscreen.value = true;
  } else {
    document.exitFullscreen?.();
    isFullscreen.value = false;
  }
}

// Listen for fullscreen exit (e.g. user presses Esc)
if (typeof document !== 'undefined') {
  document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement) isFullscreen.value = false;
  });
}

function getParams() {
  const params = {};
  if (dateRange.value && dateRange.value[0] && dateRange.value[1]) {
    params.start = dateRange.value[0].format('YYYY-MM-DD');
    params.end = dateRange.value[1].format('YYYY-MM-DD');
    params.days = dateRange.value[1].diff(dateRange.value[0], 'days') + 1;
  } else {
    params.days = 30;
  }
  return params;
}

async function fetchAll() {
  loading.value = true;
  try {
    const params = getParams();
    const [trendsRes, topRes, summaryRes, catRes, hourlyRes, recentRes] = await Promise.all([
      request.get('/admin/api/analytics/trends', { params }),
      request.get('/admin/api/analytics/top-sites', { params }),
      request.get('/admin/api/analytics/summary', { params }),
      request.get('/admin/api/analytics/top-categories', { params }),
      request.get('/admin/api/analytics/hourly', { params }),
      request.get('/admin/api/analytics/recent-clicks', { params: { limit: 20 } }),
    ]);
    trends.value = trendsRes.data?.data || [];
    topSites.value = topRes.data?.data || [];
    Object.assign(summary, summaryRes.data?.data || {});
    topCategories.value = catRes.data?.data || [];
    hourlyData.value = hourlyRes.data?.data || [];
    recentClicks.value = recentRes.data?.data || [];
  } finally {
    loading.value = false;
  }
}

async function fetchSummary() {
  try {
    const params = getParams();
    const { data } = await request.get('/admin/api/analytics/summary', { params });
    Object.assign(summary, data?.data || {});
  } catch { /* ignore */ }
}

async function fetchRecentClicks() {
  try {
    const { data } = await request.get('/admin/api/analytics/recent-clicks', { params: { limit: 20 } });
    recentClicks.value = data?.data || [];
  } catch { /* ignore */ }
}

// Computed
const maxCount = computed(() => Math.max(...trends.value.map(t => t.count), 1));
const maxClicks = computed(() => Math.max(...topSites.value.map(s => s.clicks), 1));
const maxCategoryClicks = computed(() => Math.max(...topCategories.value.map(c => c.clicks), 1));
const maxHourly = computed(() => Math.max(...hourlyData.value.map(h => h.count), 1));
const hasHourlyData = computed(() => hourlyData.value.some(h => h.count > 0));

function getBarHeight(count) {
  return maxCount.value > 0 ? (count / maxCount.value) * 180 : 0;
}

function getHourlyHeight(count) {
  return maxHourly.value > 0 ? (count / maxHourly.value) * 150 : 0;
}

function getClicksPercent(clicks) {
  return maxClicks.value > 0 ? (clicks / maxClicks.value) * 100 : 0;
}

function getCategoryPercent(clicks) {
  return maxCategoryClicks.value > 0 ? (clicks / maxCategoryClicks.value) * 100 : 0;
}

function formatShortDate(date) {
  if (!date) return '';
  const parts = date.split('-');
  return parts.length >= 3 ? `${parts[1]}/${parts[2]}` : date;
}

function formatTime(datetime) {
  if (!datetime) return '';
  return datetime.replace(/^\d{4}-/, '').replace('-', '/');
}
</script>

<style scoped>
/* Fullscreen dashboard */
.dashboard-fullscreen {
  background: #0a1628;
  min-height: 100vh;
  padding: 20px;
  color: #e0e0e0;
}

.dashboard-fullscreen :deep(.ant-card) {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.08);
}

.dashboard-fullscreen :deep(.ant-card-head) {
  color: #fff;
  border-bottom-color: rgba(255, 255, 255, 0.08);
}

.dashboard-fullscreen :deep(.ant-card-head-title) {
  color: #fff;
}

.dashboard-fullscreen :deep(.ant-statistic-title) {
  color: rgba(255, 255, 255, 0.6);
}

.dashboard-fullscreen :deep(.ant-table) {
  background: transparent;
  color: #e0e0e0;
}

.dashboard-fullscreen :deep(.ant-table-thead > tr > th) {
  background: rgba(255, 255, 255, 0.04);
  color: rgba(255, 255, 255, 0.7);
  border-bottom-color: rgba(255, 255, 255, 0.08);
}

.dashboard-fullscreen :deep(.ant-table-tbody > tr > td) {
  border-bottom-color: rgba(255, 255, 255, 0.06);
  color: #ccc;
}

.dashboard-fullscreen :deep(.ant-table-tbody > tr:hover > td) {
  background: rgba(255, 255, 255, 0.04);
}

.dashboard-fullscreen :deep(.ant-empty-description) {
  color: rgba(255, 255, 255, 0.4);
}

.dashboard-fullscreen :deep(.ant-btn) {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.15);
  color: #ccc;
}

.fullscreen-title {
  color: #fff;
  font-size: 20px;
  font-weight: 500;
  margin: 0;
}

/* Toolbar */
.page-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

/* Stat cards */
.stat-card {
  text-align: center;
}

.stat-sub {
  margin-top: 4px;
  font-size: 12px;
  color: #52c41a;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.live-dot {
  width: 6px;
  height: 6px;
  background: #52c41a;
  border-radius: 50%;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.stat-growth {
  margin-top: 4px;
  font-size: 12px;
  color: #999;
}

.stat-growth.positive { color: #52c41a; }
.stat-growth.negative { color: #ff4d4f; }

/* Trend chart */
.trend-chart {
  display: flex;
  gap: 8px;
  padding: 8px 0;
}

.trend-y-axis {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-end;
  font-size: 10px;
  color: #999;
  padding: 0 4px 24px 0;
  min-width: 40px;
  height: 204px;
}

.trend-bars {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  flex: 1;
  overflow-x: auto;
  border-bottom: 1px solid #f0f0f0;
}

.trend-bar-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 24px;
  flex: 1;
  position: relative;
}

.trend-bar {
  width: 100%;
  min-height: 2px;
  border-radius: 3px 3px 0 0;
  background: linear-gradient(180deg, #4096ff 0%, #1677ff 100%);
  transition: height 0.3s ease;
  cursor: pointer;
}

.trend-bar:hover {
  background: linear-gradient(180deg, #69b1ff 0%, #4096ff 100%);
}

.trend-bar-group:hover .trend-tooltip {
  display: block;
}

.trend-tooltip {
  display: none;
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.75);
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  text-align: center;
  white-space: nowrap;
  z-index: 10;
  pointer-events: none;
}

.trend-label {
  font-size: 10px;
  color: #999;
  margin-top: 4px;
  white-space: nowrap;
}

/* Hourly chart */
.hourly-chart {
  padding: 8px 0;
}

.hourly-bars {
  display: flex;
  align-items: flex-end;
  gap: 1px;
  height: 190px;
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 0;
}

.hourly-bar-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  min-width: 0;
}

.hourly-bar {
  width: 100%;
  min-height: 2px;
  border-radius: 2px 2px 0 0;
  background: linear-gradient(180deg, #36cfc9 0%, #13a8a8 100%);
  transition: height 0.3s ease;
  cursor: pointer;
}

.hourly-bar:hover {
  background: linear-gradient(180deg, #5cdbd3 0%, #36cfc9 100%);
}

.hourly-bar-group:hover .hourly-tooltip {
  display: block;
}

.hourly-tooltip {
  display: none;
  position: absolute;
  bottom: calc(100% + 6px);
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.75);
  color: #fff;
  padding: 3px 6px;
  border-radius: 4px;
  font-size: 11px;
  text-align: center;
  white-space: nowrap;
  z-index: 10;
  pointer-events: none;
}

.hourly-label {
  font-size: 10px;
  color: #999;
  margin-top: 4px;
}

/* Fullscreen hourly bar color */
.dashboard-fullscreen .hourly-bar {
  background: linear-gradient(180deg, #00d4ff 0%, #0098db 100%);
}

.dashboard-fullscreen .hourly-bar:hover {
  background: linear-gradient(180deg, #36e8ff 0%, #00d4ff 100%);
}

.dashboard-fullscreen .trend-bar {
  background: linear-gradient(180deg, #00d4ff 0%, #0070cc 100%);
}

.dashboard-fullscreen .trend-bar:hover {
  background: linear-gradient(180deg, #36e8ff 0%, #00d4ff 100%);
}

/* Category ranking */
.category-item {
  margin-bottom: 14px;
}

.category-header {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
  font-size: 13px;
}

.category-rank {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 4px;
  background: #f0f0f0;
  font-size: 11px;
  font-weight: 600;
  color: #666;
  margin-right: 6px;
}

.category-item:nth-child(1) .category-rank { background: #fff7e6; color: #d48806; }
.category-item:nth-child(2) .category-rank { background: #f0f5ff; color: #1677ff; }
.category-item:nth-child(3) .category-rank { background: #f6ffed; color: #52c41a; }

.category-name { flex: 1; }
.category-count { font-weight: 600; color: #1677ff; }

.category-bar-bg {
  height: 6px;
  background: #f5f5f5;
  border-radius: 3px;
  overflow: hidden;
}

.category-bar {
  height: 100%;
  background: linear-gradient(90deg, #1677ff, #69b1ff);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.dashboard-fullscreen .category-count { color: #00d4ff; }
.dashboard-fullscreen .category-bar { background: linear-gradient(90deg, #00d4ff, #36e8ff); }

/* Clicks bar */
.clicks-bar-bg {
  width: 100px;
  height: 6px;
  background: #f5f5f5;
  border-radius: 3px;
  overflow: hidden;
}

.clicks-bar {
  height: 100%;
  background: linear-gradient(90deg, #1677ff, #69b1ff);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.dashboard-fullscreen .clicks-bar { background: linear-gradient(90deg, #00d4ff, #36e8ff); }
</style>
