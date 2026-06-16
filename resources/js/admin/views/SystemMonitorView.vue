<template>
  <div>
    <PageToolbar>
      <template #right>
        <a-button :loading="clearing" danger @click="handleClearCache"><DeleteOutlined /> 清理缓存</a-button>
        <a-button :loading="loading" @click="loadInfo(true)"><ReloadOutlined /> 刷新</a-button>
      </template>
    </PageToolbar>

    <a-skeleton v-if="loading && !info" active :paragraph="{ rows: 8 }" class="admin-card" style="padding: 24px" />

    <div v-if="info" class="monitor-grid">
      <!-- 应用信息 -->
      <div class="admin-card monitor-card admin-card-hover">
        <div class="monitor-card-title">
          <span class="monitor-title-chip monitor-title-chip--primary"><AppstoreOutlined /></span>
          应用信息
        </div>
        <div class="monitor-card-body">
          <div class="info-row"><span class="info-label">应用版本</span><span class="info-value">{{ info.app?.version || '-' }}</span></div>
          <div class="info-row"><span class="info-label">Laravel</span><span class="info-value">{{ info.app?.laravel_version || '-' }}</span></div>
          <div class="info-row"><span class="info-label">环境</span><a-tag :color="info.app?.env === 'production' ? 'red' : 'green'">{{ info.app?.env || '-' }}</a-tag></div>
          <div class="info-row"><span class="info-label">站点 URL</span><span class="info-value info-mono">{{ info.app?.url || '-' }}</span></div>
          <div class="info-row"><span class="info-label">时区</span><span class="info-value">{{ info.app?.timezone || '-' }}</span></div>
          <div class="info-row"><span class="info-label">安装时间</span><span class="info-value">{{ formatDate(info.app?.installed_at) }}</span></div>
        </div>
      </div>

      <!-- PHP 信息 -->
      <div class="admin-card monitor-card admin-card-hover">
        <div class="monitor-card-title">
          <span class="monitor-title-chip monitor-title-chip--info"><CodeOutlined /></span>
          PHP 信息
        </div>
        <div class="monitor-card-body">
          <div class="info-row"><span class="info-label">PHP 版本</span><span class="info-value">{{ info.php?.version || '-' }}</span></div>
          <div class="info-row"><span class="info-label">SAPI</span><span class="info-value">{{ info.php?.sapi || '-' }}</span></div>
          <div class="info-row"><span class="info-label">memory_limit</span><span class="info-value">{{ info.php?.memory_limit || '-' }}</span></div>
          <div class="info-row"><span class="info-label">max_execution_time</span><span class="info-value">{{ info.php?.max_execution_time || '-' }}s</span></div>
          <div class="info-row"><span class="info-label">upload_max_filesize</span><span class="info-value">{{ info.php?.upload_max_filesize || '-' }}</span></div>
          <div class="info-row"><span class="info-label">post_max_size</span><span class="info-value">{{ info.php?.post_max_size || '-' }}</span></div>
          <div class="info-row"><span class="info-label">php.ini</span><span class="info-value info-mono info-path">{{ info.php?.ini_path || '-' }}</span></div>
          <div class="ext-list">
            <div class="ext-label">已加载扩展 ({{ (info.php?.extensions || []).length }})</div>
            <div class="ext-tags">
              <a-tag v-for="ext in (info.php?.extensions || [])" :key="ext">{{ ext }}</a-tag>
            </div>
          </div>
        </div>
      </div>

      <!-- 数据库 -->
      <div class="admin-card monitor-card admin-card-hover">
        <div class="monitor-card-title">
          <span class="monitor-title-chip monitor-title-chip--success"><DatabaseOutlined /></span>
          数据库
        </div>
        <div class="monitor-card-body">
          <template v-if="info.database && !info.database.error">
            <div class="info-row"><span class="info-label">驱动</span><span class="info-value">{{ info.database.driver }}</span></div>
            <div class="info-row"><span class="info-label">版本</span><span class="info-value">{{ info.database.version }}</span></div>
            <div class="info-row"><span class="info-label">数据库大小</span><span class="info-value">{{ info.database.size_mb }} MB</span></div>
            <div class="info-row"><span class="info-label">活动连接</span><span class="info-value">{{ info.database.connections }}</span></div>
            <a-table
              v-if="info.database.tables && Object.keys(info.database.tables).length"
              :data-source="tableRows"
              :columns="tableColumns"
              :pagination="false"
              row-key="name"
              size="small"
              style="margin-top: 12px"
            />
          </template>
          <a-alert v-else message="数据库信息获取失败" :description="info.database?.error" type="error" />
        </div>
      </div>

      <!-- 缓存 -->
      <div class="admin-card monitor-card admin-card-hover">
        <div class="monitor-card-title">
          <span class="monitor-title-chip monitor-title-chip--warning"><ThunderboltOutlined /></span>
          缓存
        </div>
        <div class="monitor-card-body">
          <div class="info-row"><span class="info-label">驱动</span><span class="info-value">{{ info.cache?.driver || '-' }}</span></div>
          <div class="info-row">
            <span class="info-label">状态</span>
            <a-tag :color="cacheOk ? 'green' : 'red'">{{ info.cache?.status || '-' }}</a-tag>
          </div>
          <template v-if="info.cache?.redis">
            <div class="info-row"><span class="info-label">Redis 版本</span><span class="info-value">{{ info.cache.redis.version }}</span></div>
            <div class="info-row"><span class="info-label">已用内存</span><span class="info-value">{{ info.cache.redis.used_memory }}</span></div>
            <div class="info-row"><span class="info-label">连接数</span><span class="info-value">{{ info.cache.redis.connected_clients }}</span></div>
            <div class="info-row"><span class="info-label">运行天数</span><span class="info-value">{{ info.cache.redis.uptime_days }} 天</span></div>
          </template>
        </div>
      </div>

      <!-- 磁盘 -->
      <div class="admin-card monitor-card admin-card-hover">
        <div class="monitor-card-title">
          <span class="monitor-title-chip monitor-title-chip--cyan"><HddOutlined /></span>
          磁盘使用
        </div>
        <div class="monitor-card-body">
          <a-progress :percent="info.storage?.disk_percent || 0" :stroke-color="diskColor" style="margin-bottom: 16px" />
          <div class="info-row"><span class="info-label">总空间</span><span class="info-value">{{ info.storage?.disk_total || '-' }}</span></div>
          <div class="info-row"><span class="info-label">已用</span><span class="info-value">{{ info.storage?.disk_used || '-' }}</span></div>
          <div class="info-row"><span class="info-label">可用</span><span class="info-value">{{ info.storage?.disk_free || '-' }}</span></div>
          <div class="info-row"><span class="info-label">使用率</span><span class="info-value">{{ info.storage?.disk_percent || 0 }}%</span></div>
        </div>
      </div>

      <!-- 队列 -->
      <div class="admin-card monitor-card admin-card-hover">
        <div class="monitor-card-title">
          <span class="monitor-title-chip monitor-title-chip--danger"><ClusterOutlined /></span>
          队列
        </div>
        <div class="monitor-card-body">
          <div class="info-row"><span class="info-label">驱动</span><span class="info-value">{{ info.queue?.driver || '-' }}</span></div>
          <div class="info-row"><span class="info-label">待处理</span><span class="info-value">{{ info.queue?.pending || 0 }}</span></div>
          <div class="info-row">
            <span class="info-label">失败</span>
            <span class="info-value" :style="{ color: (info.queue?.failed || 0) > 0 ? 'var(--admin-destructive)' : 'inherit', fontWeight: (info.queue?.failed || 0) > 0 ? 600 : 400 }">{{ info.queue?.failed || 0 }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { message } from 'antdv-next';
import {
  AppstoreOutlined, CodeOutlined, DatabaseOutlined, ThunderboltOutlined,
  HddOutlined, ClusterOutlined, DeleteOutlined, ReloadOutlined,
} from '@ant-design/icons-vue';
import request from '../utils/request';
import PageToolbar from '../components/PageToolbar.vue';

const loading = ref(true);
const clearing = ref(false);
const info = ref(null);

const cacheOk = computed(() => {
  const s = info.value?.cache?.status;
  return s === 'connected' || s === 'active';
});

const diskColor = computed(() => {
  const p = info.value?.storage?.disk_percent || 0;
  if (p >= 90) return '#f5222d';
  if (p >= 70) return '#faad14';
  return '#fc7c3c';
});

const tableRows = computed(() => {
  if (!info.value?.database?.tables) return [];
  return Object.entries(info.value.database.tables).map(([name, data]) => ({
    name, rows: data.rows, size_mb: data.size_mb,
  }));
});

const tableColumns = [
  { title: '表名', dataIndex: 'name', key: 'name' },
  { title: '行数', dataIndex: 'rows', key: 'rows' },
  { title: '大小 (MB)', dataIndex: 'size_mb', key: 'size_mb' },
];

onMounted(() => loadInfo(false));

async function loadInfo(refresh) {
  loading.value = true;
  try {
    const { data } = await request.get('/admin/api/system/info', { params: refresh ? { refresh: 1 } : {} });
    info.value = data;
    if (refresh) message.success('已刷新');
  } catch {
    message.error('加载系统信息失败');
  } finally {
    loading.value = false;
  }
}

async function handleClearCache() {
  clearing.value = true;
  try {
    const { data } = await request.post('/admin/api/system/clear-cache');
    message.success(data.message || '缓存已清理');
    loadInfo(true);
  } catch {
    message.error('清理缓存失败');
  } finally {
    clearing.value = false;
  }
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleString('zh-CN');
}
</script>

<style scoped>
.monitor-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 16px;
}

.monitor-card {
  overflow: hidden;
}

.monitor-card-title {
  padding: 16px 20px;
  font-size: 15px;
  font-weight: 600;
  color: var(--admin-card-foreground);
  border-bottom: 1px solid var(--admin-border-light);
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: -0.01em;
}

.monitor-title-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  font-size: 15px;
}
.monitor-title-chip--primary { background: rgba(252, 124, 60, 0.12); color: #fc7c3c; }
.monitor-title-chip--info { background: rgba(114, 46, 209, 0.12); color: #722ed1; }
.monitor-title-chip--success { background: rgba(82, 196, 26, 0.12); color: #52c41a; }
.monitor-title-chip--warning { background: rgba(250, 173, 20, 0.12); color: #faad14; }
.monitor-title-chip--danger { background: rgba(245, 34, 45, 0.12); color: #f5222d; }
.monitor-title-chip--cyan { background: rgba(13, 148, 136, 0.12); color: #0d9488; }

.monitor-card-body {
  padding: 16px 20px;
}

.info-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid var(--admin-border-light);
}
.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 13px;
  color: var(--admin-muted-foreground);
}

.info-value {
  font-size: 14px;
  font-weight: 500;
  color: var(--admin-card-foreground);
  text-align: right;
  max-width: 60%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.info-mono {
  font-family: 'JetBrains Mono', monospace;
  font-size: 13px;
}

.info-path {
  white-space: normal;
  word-break: break-all;
  font-size: 12px;
}

.ext-list {
  margin-top: 12px;
}

.ext-label {
  font-size: 13px;
  color: var(--admin-muted-foreground);
  margin-bottom: 8px;
}

.ext-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

:deep(.ant-table-thead > tr > th) {
  background: transparent;
  font-weight: 600;
  color: var(--admin-muted-foreground);
  border-bottom: 1px solid var(--admin-border-light);
}

:deep(.ant-table-tbody > tr > td) {
  border-bottom: 1px solid var(--admin-border-light);
}
</style>
