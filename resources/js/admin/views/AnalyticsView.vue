<template>
  <div :class="{ 'dashboard-fullscreen': isFullscreen }">
    <!-- Toolbar -->
    <PageToolbar v-if="!isFullscreen">
      <template #left>
        <a-range-picker v-model:value="dateRange" :presets="presetRanges" format="YYYY-MM-DD" @change="fetchAll" style="width: 260px" />
      </template>
      <template #right>
        <a-button @click="toggleFullscreen">全屏模式</a-button>
      </template>
    </PageToolbar>
    <div v-else class="fullscreen-header">
      <h2 class="fullscreen-title">数据统计大屏</h2>
      <a-button @click="toggleFullscreen">退出大屏</a-button>
    </div>

    <!-- Summary cards -->
    <a-row :gutter="[16, 16]" style="margin-bottom: 16px">
      <a-col :xs="24" :sm="12" :lg="6">
        <div class="stat-card admin-card admin-card-hover">
          <div class="stat-card-body">
            <div class="icon-chip icon-chip--primary"><ThunderboltOutlined /></div>
            <div class="stat-info">
              <div class="stat-value">{{ summary.today_clicks }}</div>
              <div class="stat-label">今日点击</div>
              <div v-if="summary.today_clicks > 0" class="stat-sub-text"><span class="live-dot"></span> 实时更新中</div>
            </div>
          </div>
        </div>
      </a-col>
      <a-col :xs="24" :sm="12" :lg="6">
        <div class="stat-card admin-card admin-card-hover">
          <div class="stat-card-body">
            <div class="icon-chip icon-chip--danger"><BarChartOutlined /></div>
            <div class="stat-info">
              <div class="stat-value">{{ summary.total_clicks }}</div>
              <div class="stat-label">总点击量</div>
              <div class="stat-growth" :class="{ positive: summary.click_growth > 0, negative: summary.click_growth < 0 }">
                <template v-if="summary.click_growth > 0">↑</template>
                <template v-else-if="summary.click_growth < 0">↓</template>
                {{ Math.abs(summary.click_growth) }}% 环比
              </div>
            </div>
          </div>
        </div>
      </a-col>
      <a-col :xs="24" :sm="12" :lg="6">
        <div class="stat-card admin-card admin-card-hover">
          <div class="stat-card-body">
            <div class="icon-chip icon-chip--success"><TeamOutlined /></div>
            <div class="stat-info">
              <div class="stat-value">{{ summary.unique_visitors }}</div>
              <div class="stat-label">独立访客</div>
            </div>
          </div>
        </div>
      </a-col>
      <a-col :xs="24" :sm="12" :lg="6">
        <div class="stat-card admin-card admin-card-hover">
          <div class="stat-card-body">
            <div class="icon-chip icon-chip--warning"><RiseOutlined /></div>
            <div class="stat-info">
              <div class="stat-value">{{ summary.avg_daily_clicks?.toFixed(1) }}</div>
              <div class="stat-label">日均点击</div>
            </div>
          </div>
        </div>
      </a-col>
    </a-row>

    <a-row :gutter="16" style="margin-bottom: 16px">
      <!-- Trends Chart -->
      <a-col :span="12">
        <div class="admin-card chart-card">
          <div class="chart-card-title"><LineChartOutlined style="color: var(--admin-primary)" /> 点击趋势</div>
          <div class="chart-card-body">
            <TrendChart v-if="trends.length" :data="trends" />
            <a-empty v-else description="数据积累中，暂无趋势数据" />
          </div>
        </div>
      </a-col>

      <!-- Hourly Distribution -->
      <a-col :span="12">
        <div class="admin-card chart-card">
          <div class="chart-card-title"><ClockCircleOutlined style="color: var(--admin-primary)" /> 时段分布</div>
          <div class="chart-card-body">
            <HourlyChart v-if="hasHourlyData" :data="hourlyData" />
            <a-empty v-else description="数据积累中，暂无时段数据" />
          </div>
        </div>
      </a-col>
    </a-row>

    <a-row :gutter="16" style="margin-bottom: 16px">
      <!-- Top Categories -->
      <a-col :span="8">
        <div class="admin-card chart-card">
          <div class="chart-card-title"><AppstoreOutlined style="color: var(--admin-primary)" /> 热门分类</div>
          <div class="chart-card-body">
            <CategoryChart v-if="topCategories.length" :data="topCategories" />
            <a-empty v-else description="数据积累中" />
          </div>
        </div>
      </a-col>

      <!-- Top Sites -->
      <a-col :span="16">
        <div class="admin-card chart-card">
          <div class="chart-card-title"><FireOutlined style="color: var(--admin-primary)" /> 热门站点</div>
          <a-table v-if="topSites.length" :dataSource="topSites" :columns="topColumns" :pagination="false" size="small" row-key="site_id">
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'site'">
                <div class="site-cell">
                  <img v-if="record.site?.favicon_url" :src="record.site.favicon_url" class="site-favicon" />
                  <div>
                    <div class="site-title">{{ record.site?.title || '-' }}</div>
                    <div class="site-url">{{ record.site?.url || '' }}</div>
                  </div>
                </div>
              </template>
              <template v-if="column.key === 'clicks'">
                <div class="clicks-cell">
                  <div class="clicks-bar-bg">
                    <div class="clicks-bar" :style="{ width: getClicksPercent(record.clicks) + '%' }"></div>
                  </div>
                  <span class="clicks-num">{{ record.clicks }}</span>
                </div>
              </template>
            </template>
          </a-table>
          <a-empty v-else description="数据积累中" />
        </div>
      </a-col>
    </a-row>

    <!-- Recent Clicks -->
    <div class="admin-card chart-card">
      <div class="chart-card-title">
        <HistoryOutlined style="color: var(--admin-primary)" /> 最近点击
        <a-button size="small" style="margin-left: auto" @click="fetchRecentClicks">刷新</a-button>
      </div>
      <a-table v-if="recentClicks.length" :dataSource="recentClicks" :columns="recentColumns" :pagination="false" size="small" row-key="id">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'site_title'">
            <a v-if="record.site_url" :href="record.site_url" target="_blank" rel="noopener">{{ record.site_title }}</a>
            <span v-else>{{ record.site_title }}</span>
          </template>
          <template v-if="column.key === 'ip_address'">
            <span class="ip-cell">{{ record.ip_address }}</span>
          </template>
          <template v-if="column.key === 'clicked_at'">
            <span class="time-cell">{{ formatTime(record.clicked_at) }}</span>
          </template>
        </template>
      </a-table>
      <a-empty v-else description="暂无点击记录" />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import dayjs from 'dayjs';
import {
  ThunderboltOutlined, BarChartOutlined, TeamOutlined, RiseOutlined,
  LineChartOutlined, ClockCircleOutlined, AppstoreOutlined, FireOutlined,
  HistoryOutlined,
} from '@ant-design/icons-vue';
import request from '../utils/request';
import PageToolbar from '../components/PageToolbar.vue';
import TrendChart from '../components/charts/TrendChart.vue';
import HourlyChart from '../components/charts/HourlyChart.vue';
import CategoryChart from '../components/charts/CategoryChart.vue';

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

// Presets MUST be dayjs objects, not native Date — antdv RangePicker calls
// .format() / .diff() on hover which crash with "undefined is not a function"
// (and then infinite-loop the scheduler) when given plain Date instances.
const presetRanges = [
  { label: '最近 7 天', value: [dayjs().subtract(7, 'day'), dayjs()] },
  { label: '最近 30 天', value: [dayjs().subtract(30, 'day'), dayjs()] },
  { label: '最近 90 天', value: [dayjs().subtract(90, 'day'), dayjs()] },
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
  document.removeEventListener('fullscreenchange', onFullscreenChange);
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
function onFullscreenChange() {
  if (!document.fullscreenElement) isFullscreen.value = false;
}
if (typeof document !== 'undefined') {
  document.addEventListener('fullscreenchange', onFullscreenChange);
}

function getParams() {
  const params = {};
  if (dateRange.value && dateRange.value[0] && dateRange.value[1]) {
    // Coerce to dayjs in case the picker ever hands us a native Date.
    const start = dayjs(dateRange.value[0]);
    const end = dayjs(dateRange.value[1]);
    params.start = start.format('YYYY-MM-DD');
    params.end = end.format('YYYY-MM-DD');
    params.days = end.diff(start, 'days') + 1;
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
.dashboard-fullscreen {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #0a1628;
  padding: 24px;
  overflow-y: auto;
}
.dashboard-fullscreen :deep(.admin-card) {
  background: rgba(15, 35, 60, 0.8) !important;
  color: #e5e7eb !important;
  border-color: rgba(0, 212, 255, 0.2) !important;
}
.dashboard-fullscreen .stat-value { color: #00d4ff !important; }
.dashboard-fullscreen .stat-label { color: rgba(255,255,255,0.6) !important; }
.dashboard-fullscreen .chart-card-title { color: #00d4ff !important; }

.fullscreen-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.fullscreen-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
}

/* Stat cards */
.stat-card { padding: 20px; }
.stat-card-body { display: flex; align-items: center; gap: 16px; }
.stat-info { flex: 1; min-width: 0; }
.stat-value {
  font-size: 28px; font-weight: 700; line-height: 1.2;
  color: var(--admin-card-foreground);
}
.stat-label {
  font-size: 13px; color: var(--admin-muted-foreground); margin-top: 4px;
}
.stat-sub-text {
  font-size: 12px; color: var(--admin-success); margin-top: 6px;
  display: flex; align-items: center; gap: 4px;
}
.live-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--admin-success); animation: pulse 1.5s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
.stat-growth {
  font-size: 12px; font-weight: 500; margin-top: 6px;
}
.stat-growth.positive { color: var(--admin-success); }
.stat-growth.negative { color: var(--admin-destructive); }

/* Chart cards */
.chart-card {
  padding: 0;
  overflow: hidden;
  margin-bottom: 16px;
}
.chart-card-title {
  padding: 16px 20px;
  font-size: 16px; font-weight: 600;
  color: var(--admin-card-foreground);
  border-bottom: 1px solid var(--admin-border-light);
  display: flex; align-items: center; gap: 8px;
}
.chart-card-body { padding: 16px 20px; }

/* Site cell in top sites table */
.site-cell { display: flex; align-items: center; gap: 8px; }
.site-favicon { width: 16px; height: 16px; border-radius: 2px; flex-shrink: 0; }
.site-title { font-weight: 500; font-size: 13px; }
.site-url { font-size: 11px; color: var(--admin-muted-foreground); }

/* Clicks bar */
.clicks-cell { display: flex; align-items: center; gap: 8px; }
.clicks-bar-bg {
  flex: 1; height: 8px; background: var(--admin-muted);
  border-radius: 4px; overflow: hidden;
}
.clicks-bar {
  height: 100%; border-radius: 4px;
  background: linear-gradient(90deg, rgba(252,124,60,0.6), var(--admin-primary));
}
.clicks-num { font-weight: 600; min-width: 40px; color: var(--admin-primary); }

/* Table cells */
.ip-cell { font-family: monospace; font-size: 12px; }
.time-cell { color: var(--admin-muted-foreground); font-size: 12px; }

:deep(.ant-table-thead > tr > th) {
  background: transparent; font-weight: 600;
  color: var(--admin-muted-foreground);
  border-bottom: 1px solid var(--admin-border-light);
}
:deep(.ant-table-tbody > tr > td) { border-bottom: 1px solid var(--admin-border-light); }
:deep(.ant-table-tbody > tr:hover > td) { background: var(--admin-muted) !important; }
</style>
