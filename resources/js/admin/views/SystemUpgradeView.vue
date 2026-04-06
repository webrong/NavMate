<template>
  <div style="max-width: 1000px; margin: 0 auto">
    <a-card title="系统升级" :bordered="false">
      <div style="margin-top: 16px">
        <!-- 当前版本 + 检查更新 -->
        <a-card size="small" style="margin-bottom: 16px">
          <a-row align="middle" :gutter="16">
            <a-col>
              <div style="font-size: 13px; color: #999">当前版本</div>
              <div style="font-size: 20px; font-weight: 600; color: #1677ff">{{ currentVersion }}</div>
            </a-col>
            <a-col flex="auto" style="text-align: right">
              <a-button type="primary" :loading="checking" @click="checkUpdate">
                检查更新
              </a-button>
            </a-col>
          </a-row>
        </a-card>

        <!-- 最新版本信息 -->
        <template v-if="updateInfo">
          <a-alert
            v-if="updateInfo.error"
            :message="updateInfo.error"
            type="warning"
            show-icon
            style="margin-bottom: 16px"
          />
          <template v-else>
            <!-- 有新版本 -->
            <a-card
              v-if="updateInfo.has_update"
              size="small"
              style="margin-bottom: 16px; border-color: #52c41a"
            >
              <template #title>
                <span style="color: #52c41a">发现新版本: {{ updateInfo.latest_version }}</span>
              </template>
              <template #extra>
                <a-button
                  type="primary"
                  danger
                  :loading="updating"
                  :disabled="updating"
                  @click="executeUpdate"
                >
                  {{ updating ? '升级中...' : '立即升级' }}
                </a-button>
              </template>
              <div v-if="updateInfo.published_at" style="font-size: 12px; color: #999; margin-bottom: 8px">
                发布于 {{ formatDate(updateInfo.published_at) }}
              </div>
              <div
                v-if="updateInfo.changelog"
                style="font-size: 13px; color: #555; white-space: pre-wrap; max-height: 300px; overflow-y: auto"
              >{{ updateInfo.changelog }}</div>
              <div v-else style="font-size: 13px; color: #999">暂无更新日志</div>
            </a-card>
            <!-- 已是最新 -->
            <a-card v-else size="small" style="margin-bottom: 16px">
              <template #title>
                <span style="color: #1677ff">最新版本: {{ updateInfo.latest_version }}</span>
                <a-tag color="green" style="margin-left: 8px">当前已是最新</a-tag>
              </template>
              <div v-if="updateInfo.published_at" style="font-size: 12px; color: #999; margin-bottom: 8px">
                发布于 {{ formatDate(updateInfo.published_at) }}
              </div>
              <div
                v-if="updateInfo.changelog"
                style="font-size: 13px; color: #555; white-space: pre-wrap; max-height: 300px; overflow-y: auto"
              >{{ updateInfo.changelog }}</div>
              <div v-else style="font-size: 13px; color: #999">暂无更新日志</div>
            </a-card>
          </template>
        </template>

        <!-- 升级进度 -->
        <a-card v-if="updating" size="small" style="margin-bottom: 16px">
          <a-spin tip="正在升级，请勿关闭页面..." :spinning="true">
            <div style="padding: 20px 0; text-align: center; color: #999">
              升级过程中站点将自动进入维护模式，升级完成后恢复
            </div>
          </a-spin>
        </a-card>

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
        <h3 style="font-size: 15px; margin-bottom: 12px; color: #333">升级历史</h3>
        <a-table
          :data-source="updateLogs"
          :columns="logColumns"
          :pagination="false"
          :loading="logsLoading"
          row-key="id"
          size="small"
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
              {{ formatDate(record.created_at) }}
            </template>
          </template>
          <template #expandedRowRender="{ record }">
            <pre style="margin: 0; padding: 12px; background: #fafafa; border-radius: 6px; font-size: 12px; color: #555; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto">{{ record.log || '无详细日志' }}</pre>
          </template>
        </a-table>
      </div>
    </a-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { message } from 'antdv-next';
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
  } catch {
    // ignore
  }
}

async function loadLogs() {
  logsLoading.value = true;
  try {
    const { data } = await request.get('/admin/api/system/update-logs');
    updateLogs.value = data;
  } catch {
    // ignore
  } finally {
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
