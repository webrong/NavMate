<template>
  <div>
    <!-- 当前版本卡片 -->
    <div class="admin-card version-card">
      <div class="version-card-body">
        <div class="version-left">
          <div class="icon-chip icon-chip--primary"><CloudUploadOutlined /></div>
          <div>
            <div class="version-label">当前版本</div>
            <div class="version-number">{{ currentVersion }}</div>
          </div>
        </div>
        <a-button type="primary" :loading="checking" @click="checkUpdate">
          <ReloadOutlined /> 检查更新
        </a-button>
      </div>
    </div>

    <!-- 错误提示 -->
    <a-alert
      v-if="updateInfo?.error"
      :message="updateInfo.error"
      type="warning"
      show-icon
      style="margin-bottom: 16px"
    />

    <!-- 有新版本 -->
    <div v-if="updateInfo && updateInfo.has_update" class="admin-card update-available-card">
      <div class="update-header">
        <div class="update-title">
          <span class="new-version-badge">新版本</span>
          <span class="new-version-num">v{{ updateInfo.latest_version }}</span>
        </div>
        <a-button type="primary" danger :loading="updating" :disabled="updating" @click="executeUpdate">
          {{ updating ? '升级中...' : '立即升级' }}
        </a-button>
      </div>
      <div v-if="updateInfo.published_at" class="update-meta">发布于 {{ formatDate(updateInfo.published_at) }}</div>
      <div v-if="updateInfo.changelog" class="changelog">{{ updateInfo.changelog }}</div>
      <div v-else class="no-changelog">暂无更新日志</div>
    </div>

    <!-- 已是最新 -->
    <div v-if="updateInfo && !updateInfo.has_update && !updateInfo.error" class="admin-card latest-card">
      <div class="latest-content">
        <CheckCircleOutlined class="latest-icon" />
        <span class="latest-text">已是最新版本 v{{ updateInfo.latest_version }}</span>
      </div>
    </div>

    <!-- 升级进度 -->
    <div v-if="updating" class="admin-card upgrading-card">
      <a-spin tip="正在升级，请勿关闭页面..." :spinning="true">
        <div class="upgrading-hint">升级过程中站点将自动进入维护模式，升级完成后恢复</div>
      </a-spin>
    </div>

    <!-- 升级结果 -->
    <a-alert
      v-if="updateResult"
      :message="updateResult.message"
      :type="updateResult.success ? 'success' : 'error'"
      show-icon
      closable
      style="margin-bottom: 16px"
    />

    <!-- 升级历史 -->
    <div class="admin-card history-card">
      <div class="history-title"><HistoryOutlined style="color: var(--admin-primary)" /> 升级历史</div>
      <a-table
        :data-source="updateLogs"
        :columns="logColumns"
        :pagination="false"
        :loading="logsLoading"
        row-key="id"
        size="middle"
        :expanded-row-keys="expandedKeys"
        @expand="onExpand"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            <a-tag :color="statusColor(record.status)">{{ statusText(record.status) }}</a-tag>
          </template>
          <template v-if="column.key === 'version'">
            {{ record.from_version }} → {{ record.to_version }}
          </template>
          <template v-if="column.key === 'created_at'">
            <span class="time-cell">{{ formatDate(record.created_at) }}</span>
          </template>
        </template>
        <template #expandedRowRender="{ record }">
          <pre class="log-detail">{{ record.log || '无详细日志' }}</pre>
        </template>
      </a-table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { message } from 'antdv-next';
import {
  CloudUploadOutlined, ReloadOutlined, CheckCircleOutlined, HistoryOutlined,
} from '@ant-design/icons-vue';
import request from '../utils/request';

const checking = ref(false);
const updating = ref(false);
const currentVersion = ref('-');
const updateInfo = ref(null);
const updateResult = ref(null);
const updateLogs = ref([]);
const logsLoading = ref(false);
const expandedKeys = ref([]);

const logColumns = [
  { title: '版本', key: 'version' },
  { title: '状态', key: 'status', width: 100 },
  { title: '时间', key: 'created_at', width: 180 },
];

onMounted(() => {
  loadVersion();
  loadLogs();
});

async function loadVersion() {
  try {
    const { data } = await request.get('/admin/api/system/info');
    currentVersion.value = data.app?.version || '-';
  } catch { /* ignore */ }
}

async function loadLogs() {
  logsLoading.value = true;
  try {
    const { data } = await request.get('/admin/api/system/update-logs');
    updateLogs.value = data;
  } catch { /* ignore */ } finally {
    logsLoading.value = false;
  }
}

async function checkUpdate() {
  checking.value = true;
  updateResult.value = null;
  try {
    const { data } = await request.get('/admin/api/system/check-update');
    updateInfo.value = data;
  } catch {
    message.error('检查更新失败');
  } finally {
    checking.value = false;
  }
}

async function executeUpdate() {
  updating.value = true;
  updateResult.value = null;
  try {
    const { data } = await request.post('/admin/api/system/update');
    updateResult.value = data;
    if (data.success) {
      updateInfo.value = null;
      currentVersion.value = data.to_version;
    }
  } catch (e) {
    updateResult.value = { success: false, message: e.response?.data?.message || '升级请求失败' };
  } finally {
    updating.value = false;
    loadLogs();
  }
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleString('zh-CN');
}

function statusColor(status) {
  const map = { success: 'green', failed: 'red', rolled_back: 'orange' };
  return map[status] || 'default';
}

function statusText(status) {
  const map = { success: '成功', failed: '失败', rolled_back: '已回滚' };
  return map[status] || status;
}

function onExpand(expanded, record) {
  expandedKeys.value = expanded ? [record.id] : [];
}
</script>

<style scoped>
.version-card {
  padding: 20px 24px;
  margin-bottom: 16px;
}
.version-card-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.version-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.version-label {
  font-size: 13px;
  color: var(--admin-muted-foreground);
}
.version-number {
  font-size: 22px;
  font-weight: 700;
  color: var(--admin-primary);
}

.update-available-card {
  padding: 20px 24px;
  margin-bottom: 16px;
  border-left: 4px solid var(--admin-success);
}
.update-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.update-title {
  display: flex;
  align-items: center;
  gap: 12px;
}
.new-version-badge {
  background: var(--admin-success);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  padding: 2px 10px;
  border-radius: 4px;
}
.new-version-num {
  font-size: 18px;
  font-weight: 700;
  color: var(--admin-card-foreground);
}
.update-meta {
  font-size: 12px;
  color: var(--admin-muted-foreground);
  margin-bottom: 12px;
}
.changelog {
  font-size: 13px;
  color: var(--admin-card-foreground);
  white-space: pre-wrap;
  max-height: 300px;
  overflow-y: auto;
  line-height: 1.6;
}
.no-changelog {
  font-size: 13px;
  color: var(--admin-muted-foreground);
}

.latest-card {
  padding: 32px 24px;
  margin-bottom: 16px;
  text-align: center;
}
.latest-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.latest-icon {
  font-size: 24px;
  color: var(--admin-success);
}
.latest-text {
  font-size: 16px;
  font-weight: 600;
  color: var(--admin-card-foreground);
}

.upgrading-card {
  padding: 24px;
  margin-bottom: 16px;
  text-align: center;
}
.upgrading-hint {
  padding: 20px 0;
  color: var(--admin-muted-foreground);
  font-size: 14px;
}

.history-card {
  overflow: hidden;
}
.history-title {
  padding: 16px 24px;
  font-size: 16px;
  font-weight: 600;
  color: var(--admin-card-foreground);
  border-bottom: 1px solid var(--admin-border-light);
  display: flex;
  align-items: center;
  gap: 8px;
}

.time-cell {
  color: var(--admin-muted-foreground);
  font-size: 13px;
}

.log-detail {
  margin: 0;
  padding: 12px;
  background: var(--admin-muted);
  border-radius: 6px;
  font-size: 12px;
  color: var(--admin-muted-foreground);
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 300px;
  overflow-y: auto;
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
:deep(.ant-table-tbody > tr:hover > td) {
  background: var(--admin-muted) !important;
}
</style>
