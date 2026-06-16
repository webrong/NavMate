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
        <a-button type="primary" :loading="checking" :disabled="updating" @click="checkUpdate">
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
        <a-button type="primary" danger :disabled="updating" @click="executeUpdate">
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
      <!-- 进度条 -->
      <div class="progress-bar-wrap">
        <div class="progress-bar-track">
          <div class="progress-bar-fill" :style="{ width: progressPercent + '%' }">
            <span class="progress-bar-shine"></span>
          </div>
        </div>
        <span class="progress-percent">{{ progressPercent }}%</span>
      </div>

      <!-- 步骤列表 -->
      <div class="steps-list">
        <div
          v-for="step in steps"
          :key="step.n"
          class="step-item"
          :class="stepClass(step)"
        >
          <span class="step-icon">
            <CheckCircleOutlined v-if="step.status === 'done'" class="step-icon-done" />
            <LoadingOutlined v-else-if="step.status === 'running'" class="step-icon-running" spin />
            <span v-else class="step-icon-pending">{{ step.n }}</span>
          </span>
          <span class="step-text">{{ step.label }}</span>
        </div>
      </div>

      <!-- 实时日志 -->
      <div v-if="logLines.length" class="log-stream">
        <div v-for="(line, i) in logLines" :key="i" class="log-line">{{ line }}</div>
      </div>

      <div class="upgrading-hint">升级过程中站点将自动进入维护模式，升级完成后恢复</div>
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { message } from 'antdv-next';
import {
  CloudUploadOutlined, ReloadOutlined, CheckCircleOutlined, HistoryOutlined,
  LoadingOutlined,
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

// Upgrade progress state
const STEP_LABELS = [
  '检查更新', '开启维护模式', '备份当前文件', '备份数据库',
  '下载新版本', '解压并替换文件', '更新版本号', '运行数据库迁移',
  '清除缓存并关闭维护模式',
];
const steps = ref(STEP_LABELS.map((label, i) => ({ n: i + 1, label, status: 'pending' })));
const logLines = ref([]);

const progressPercent = computed(() => {
  const doneCount = steps.value.filter((s) => s.status === 'done').length;
  return Math.round((doneCount / 9) * 100);
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

const logColumns = [
  { title: '版本', key: 'version' },
  { title: '状态', key: 'status', width: 100 },
  { title: '时间', key: 'created_at', width: 180 },
];

onMounted(() => {
  loadVersion();
  loadLogs();
});

onUnmounted(() => {
  // Abort any in-flight upgrade stream if the component unmounts.
  if (abortController) {
    abortController.abort();
  }
});

let abortController = null;

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

/**
 * Execute upgrade via SSE stream.
 *
 * The backend streams Server-Sent Events for each step. We consume them with
 * fetch + ReadableStream (not EventSource, because EventSource doesn't support
 * POST or custom headers like our CSRF token).
 */
async function executeUpdate() {
  // Reset progress state
  steps.value = STEP_LABELS.map((label, i) => ({ n: i + 1, label, status: 'pending' }));
  logLines.value = [];
  updateResult.value = null;
  updating.value = true;

  abortController = new AbortController();

  try {
    const response = await fetch('/admin/api/system/update', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/event-stream',
      },
      credentials: 'same-origin',
      signal: abortController.signal,
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    // Read the stream chunk by chunk, parse SSE events.
    // eslint-disable-next-line no-constant-condition
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;

      buffer += decoder.decode(value, { stream: true });

      // SSE events are separated by a blank line (\n\n).
      const parts = buffer.split('\n\n');
      buffer = parts.pop(); // keep incomplete trailing chunk

      for (const part of parts) {
        handleSseEvent(part);
      }
    }
    // Flush any remaining buffered data
    if (buffer.trim()) {
      handleSseEvent(buffer);
    }
  } catch (e) {
    if (e.name === 'AbortError') return;
    updateResult.value = { success: false, message: e.message || '升级请求失败' };
  } finally {
    updating.value = false;
    abortController = null;
    loadLogs();
  }
}

/**
 * Parse and handle a single SSE event block.
 * Format: "event: step\ndata: {...}\n"
 */
function handleSseEvent(raw) {
  let eventType = 'message';
  let dataStr = '';

  for (const line of raw.split('\n')) {
    if (line.startsWith('event:')) {
      eventType = line.slice(6).trim();
    } else if (line.startsWith('data:')) {
      dataStr += line.slice(5).trim();
    }
  }

  if (!dataStr) return;

  let data;
  try {
    data = JSON.parse(dataStr);
  } catch {
    return;
  }

  if (eventType === 'step') {
    const idx = data.step - 1;
    if (idx < 0 || idx >= 9) return;

    if (data.status === 'running') {
      steps.value[idx].status = 'running';
      logLines.value.push(`[${data.step}/9] ${data.message}...`);
    } else {
      steps.value[idx].status = 'done';
      // Sub-step detail lines (db backup result, sha256, etc.)
      if (data.message && !data.message.match(/^\d/)) {
        logLines.value.push(`    ${data.message}`);
      }
    }
  } else if (eventType === 'done') {
    // Mark all remaining steps as done
    steps.value.forEach((s) => { s.status = 'done'; });
    updateResult.value = data;
    if (data.to_version) {
      updateInfo.value = null;
      currentVersion.value = data.to_version;
    }
    message.success(data.message || '升级成功');
  } else if (eventType === 'error') {
    updateResult.value = data;
    message.error(data.message || '升级失败');
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

function stepClass(step) {
  return {
    'step-done': step.status === 'done',
    'step-running': step.status === 'running',
    'step-pending': step.status === 'pending',
  };
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

/* ── Upgrade progress card ── */
.upgrading-card {
  padding: 24px;
  margin-bottom: 16px;
}

/* Progress bar with smooth fill + shine sweep */
.progress-bar-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}
.progress-bar-track {
  flex: 1;
  height: 10px;
  background: var(--admin-muted);
  border-radius: 5px;
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  background: var(--admin-primary);
  border-radius: 5px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}
/* Moving shine effect on the progress bar */
.progress-bar-shine {
  position: absolute;
  top: 0;
  left: -60%;
  width: 60%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.3),
    transparent
  );
  animation: shine-sweep 1.8s ease-in-out infinite;
}
@keyframes shine-sweep {
  0% { left: -60%; }
  100% { left: 100%; }
}
.progress-percent {
  font-size: 14px;
  font-weight: 700;
  color: var(--admin-primary);
  min-width: 42px;
  text-align: right;
}

/* Step list with fade-in status transitions */
.steps-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 20px;
}
.step-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 6px;
  transition: background 0.3s ease;
}
.step-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 14px;
  border-radius: 50%;
}
.step-icon-done {
  color: var(--admin-success);
  animation: fade-in-up 0.4s ease;
}
.step-icon-running {
  color: var(--admin-primary);
  /* Pulsing glow on the active step */
  animation: pulse-glow 1.5s ease-in-out infinite;
}
.step-icon-pending {
  color: var(--admin-muted-foreground);
  border: 1.5px solid var(--admin-border-light);
  font-size: 12px;
  font-weight: 600;
}
.step-text {
  font-size: 14px;
  transition: color 0.3s ease, font-weight 0.3s ease;
}

/* Status-based styling */
.step-done .step-text {
  color: var(--admin-success);
}
.step-running {
  background: color-mix(in srgb, var(--admin-primary) 8%, transparent);
}
.step-running .step-text {
  color: var(--admin-primary);
  font-weight: 600;
}
.step-pending .step-text {
  color: var(--admin-muted-foreground);
}

/* Animations */
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(6px) scale(0.8); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes pulse-glow {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.15); }
}

/* Live log stream */
.log-stream {
  background: var(--admin-muted);
  border-radius: 6px;
  padding: 12px;
  max-height: 180px;
  overflow-y: auto;
  margin-bottom: 16px;
}
.log-line {
  font-size: 12px;
  color: var(--admin-muted-foreground);
  font-family: 'Consolas', 'Monaco', monospace;
  line-height: 1.6;
  animation: slide-in 0.3s ease;
}
@keyframes slide-in {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

.upgrading-hint {
  text-align: center;
  color: var(--admin-muted-foreground);
  font-size: 13px;
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
