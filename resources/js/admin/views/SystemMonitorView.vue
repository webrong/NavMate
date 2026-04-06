<template>
  <div style="max-width: 1000px; margin: 0 auto">
    <a-card title="系统监控" :bordered="false" :loading="loading" :extra="extraActions">
      <div v-if="info">
        <!-- 应用信息 -->
        <h3 style="font-size: 15px; margin-bottom: 12px; color: #333">应用信息</h3>
        <a-row :gutter="[16, 12]" style="margin-bottom: 24px">
          <a-col :span="8">
            <a-statistic title="应用版本" :value="info.app?.version || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="8">
            <a-statistic title="Laravel" :value="info.app?.laravel_version || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="8">
            <a-statistic title="环境" :value="info.app?.env || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="8">
            <a-statistic title="站点 URL" :value="info.app?.url || '-'" :value-style="{ fontSize: '14px' }" />
          </a-col>
          <a-col :span="8">
            <a-statistic title="时区" :value="info.app?.timezone || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="8">
            <a-statistic title="安装时间" :value="formatDate(info.app?.installed_at) || '-'" :value-style="{ fontSize: '14px' }" />
          </a-col>
        </a-row>

        <a-divider />

        <!-- PHP 信息 -->
        <h3 style="font-size: 15px; margin-bottom: 12px; color: #333">PHP 信息</h3>
        <a-row :gutter="[16, 12]" style="margin-bottom: 16px">
          <a-col :span="6">
            <a-statistic title="PHP 版本" :value="info.php?.version || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="SAPI" :value="info.php?.sapi || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="memory_limit" :value="info.php?.memory_limit || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="max_execution_time" :value="info.php?.max_execution_time + 's' || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="upload_max_filesize" :value="info.php?.upload_max_filesize || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="post_max_size" :value="info.php?.post_max_size || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="12">
            <div style="font-size: 13px; color: #666; margin-bottom: 4px">php.ini 路径</div>
            <div style="font-size: 13px; word-break: break-all; font-family: monospace">{{ info.php?.ini_path || '-' }}</div>
          </a-col>
        </a-row>

        <div style="margin-bottom: 24px">
          <div style="font-size: 13px; color: #666; margin-bottom: 8px">已加载扩展</div>
          <div>
            <a-tag v-for="ext in (info.php?.extensions || [])" :key="ext" style="margin-bottom: 4px">{{ ext }}</a-tag>
          </div>
        </div>

        <a-divider />

        <!-- 数据库 -->
        <h3 style="font-size: 15px; margin-bottom: 12px; color: #333">数据库</h3>
        <template v-if="info.database && !info.database.error">
          <a-row :gutter="[16, 12]" style="margin-bottom: 16px">
            <a-col :span="6">
              <a-statistic title="驱动" :value="info.database.driver" :value-style="{ fontSize: '16px' }" />
            </a-col>
            <a-col :span="6">
              <a-statistic title="版本" :value="info.database.version" :value-style="{ fontSize: '16px' }" />
            </a-col>
            <a-col :span="6">
              <a-statistic title="数据库大小" :value="info.database.size_mb + ' MB'" :value-style="{ fontSize: '16px' }" />
            </a-col>
            <a-col :span="6">
              <a-statistic title="活动连接" :value="info.database.connections" :value-style="{ fontSize: '16px' }" />
            </a-col>
          </a-row>
          <a-table
            v-if="info.database.tables && Object.keys(info.database.tables).length"
            :data-source="tableRows"
            :columns="tableColumns"
            :pagination="false"
            row-key="name"
            size="small"
            style="margin-bottom: 24px"
          />
        </template>
        <a-alert v-else message="数据库信息获取失败" :description="info.database?.error" type="error" style="margin-bottom: 24px" />

        <a-divider />

        <!-- 缓存 -->
        <h3 style="font-size: 15px; margin-bottom: 12px; color: #333">缓存</h3>
        <a-row :gutter="[16, 12]" style="margin-bottom: 16px">
          <a-col :span="6">
            <a-statistic title="驱动" :value="info.cache?.driver || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="状态" :value="info.cache?.status || '-'" :value-style="{ fontSize: '16px', color: info.cache?.status === 'connected' || info.cache?.status === 'active' ? '#52c41a' : '#ff4d4f' }" />
          </a-col>
        </a-row>
        <template v-if="info.cache?.redis">
          <a-row :gutter="[16, 12]" style="margin-bottom: 24px">
            <a-col :span="6">
              <a-statistic title="Redis 版本" :value="info.cache.redis.version" :value-style="{ fontSize: '16px' }" />
            </a-col>
            <a-col :span="6">
              <a-statistic title="已用内存" :value="info.cache.redis.used_memory" :value-style="{ fontSize: '16px' }" />
            </a-col>
            <a-col :span="6">
              <a-statistic title="连接数" :value="info.cache.redis.connected_clients" :value-style="{ fontSize: '16px' }" />
            </a-col>
            <a-col :span="6">
              <a-statistic title="运行天数" :value="info.cache.redis.uptime_days + ' 天'" :value-style="{ fontSize: '16px' }" />
            </a-col>
          </a-row>
        </template>

        <a-divider />

        <!-- 磁盘 -->
        <h3 style="font-size: 15px; margin-bottom: 12px; color: #333">磁盘使用</h3>
        <div style="margin-bottom: 8px">
          <a-progress :percent="info.storage?.disk_percent || 0" :stroke-color="diskColor" />
        </div>
        <a-row :gutter="[16, 12]" style="margin-bottom: 24px">
          <a-col :span="6">
            <a-statistic title="总空间" :value="info.storage?.disk_total || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="已用" :value="info.storage?.disk_used || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="可用" :value="info.storage?.disk_free || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="6">
            <a-statistic title="使用率" :value="(info.storage?.disk_percent || 0) + '%'" :value-style="{ fontSize: '16px' }" />
          </a-col>
        </a-row>

        <a-divider />

        <!-- 队列 -->
        <h3 style="font-size: 15px; margin-bottom: 12px; color: #333">队列</h3>
        <a-row :gutter="[16, 12]">
          <a-col :span="8">
            <a-statistic title="驱动" :value="info.queue?.driver || '-'" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="8">
            <a-statistic title="待处理" :value="info.queue?.pending || 0" :value-style="{ fontSize: '16px' }" />
          </a-col>
          <a-col :span="8">
            <a-statistic title="失败" :value="info.queue?.failed || 0" :value-style="{ fontSize: '16px', color: (info.queue?.failed || 0) > 0 ? '#ff4d4f' : undefined }" />
          </a-col>
        </a-row>
      </div>
    </a-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { message } from 'antdv-next';
import { ReloadOutlined } from '@ant-design/icons-vue';
import request from '../utils/request';

const loading = ref(true);
const info = ref(null);

const diskColor = computed(() => {
  const p = info.value?.storage?.disk_percent || 0;
  if (p >= 90) return '#ff4d4f';
  if (p >= 70) return '#faad14';
  return '#1677ff';
});

const tableRows = computed(() => {
  if (!info.value?.database?.tables) return [];
  return Object.entries(info.value.database.tables).map(([name, data]) => ({
    name,
    rows: data.rows,
    size_mb: data.size_mb,
  }));
});

const tableColumns = [
  { title: '表名', dataIndex: 'name', key: 'name' },
  { title: '行数', dataIndex: 'rows', key: 'rows' },
  { title: '大小 (MB)', dataIndex: 'size_mb', key: 'size_mb' },
];

const extraActions = computed(() => {
  return h('a-button', {
    size: 'small',
    loading: loading.value,
    onClick: () => loadInfo(true),
  }, () => '刷新');
});

onMounted(() => loadInfo(false));

async function loadInfo(refresh) {
  loading.value = true;
  try {
    const { data } = await request.get('/admin/api/system/info' + (refresh ? '?refresh=1' : ''));
    info.value = data;
    if (refresh) message.success('已刷新');
  } catch {
    message.error('加载系统信息失败');
  } finally {
    loading.value = false;
  }
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleString('zh-CN');
}
</script>
