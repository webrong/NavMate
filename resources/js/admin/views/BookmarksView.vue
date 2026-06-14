<template>
  <div>
    <div class="admin-card bookmark-card">
      <div class="bookmark-title"><ImportOutlined style="color: var(--admin-primary)" /> 书签导入</div>

      <div class="bookmark-body">
        <!-- 上传区 -->
        <div v-if="!previewData && !importResult">
          <a-upload-dragger
            name="bookmark_file"
            :accept="['.html', '.htm']"
            :max-count="1"
            :before-upload="beforeUpload"
            :customRequest="handleUpload"
          >
            <p class="ant-upload-drag-icon">
              <InboxOutlined style="font-size: 48px; color: var(--admin-primary)" />
            </p>
            <p class="upload-text">点击或拖拽上传书签文件</p>
            <p class="upload-hint">支持 Chrome / Firefox / Edge 导出的 HTML 书签文件</p>
          </a-upload-dragger>
        </div>

        <!-- 预览 -->
        <div v-if="previewData" class="preview-section">
          <a-divider>预览结果</a-divider>
          <a-row :gutter="16">
            <a-col :span="12">
              <div class="preview-stat">
                <div class="preview-stat-value">{{ previewData.total_bookmarks || 0 }}</div>
                <div class="preview-stat-label">书签数</div>
              </div>
            </a-col>
            <a-col :span="12">
              <div class="preview-stat">
                <div class="preview-stat-value">{{ previewData.total_folders || 0 }}</div>
                <div class="preview-stat-label">文件夹</div>
              </div>
            </a-col>
          </a-row>
          <a-checkbox v-model:checked="skipDuplicates" style="margin: 16px 0">跳过重复书签</a-checkbox>
          <div class="folder-preview">
            <div v-for="folder in previewData.folders" :key="folder.name" class="folder-item">
              <div class="folder-name">{{ folder.name }} ({{ folder.children ? folder.children.length : 0 }})</div>
              <div class="folder-tags">
                <a-tag v-for="item in (folder.children || []).slice(0, 5)" :key="item.title">{{ item.title }}</a-tag>
                <span v-if="(folder.children || []).length > 5" class="more-hint">...等 {{ (folder.children || []).length - 5 }} 个</span>
              </div>
            </div>
          </div>
          <div class="preview-actions">
            <a-button @click="resetPreview">重新选择</a-button>
            <a-button type="primary" :loading="importing" :disabled="!previewData" @click="handleImport">开始导入</a-button>
          </div>
        </div>

        <!-- 结果 -->
        <a-result v-if="importResult" status="success" title="导入完成" :sub-title="importResult.message">
          <template #extra>
            <a-button type="primary" @click="resetImport">继续导入</a-button>
          </template>
        </a-result>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { message } from 'antdv-next';
import request from '../utils/request';
import { InboxOutlined, ImportOutlined } from '@ant-design/icons-vue';

const previewData = ref(null);
const importResult = ref(null);
const skipDuplicates = ref(true);
const importing = ref(false);
const bookmarkHtml = ref('');

function beforeUpload(file) {
  const reader = new FileReader();
  reader.onload = (e) => { bookmarkHtml.value = e.target.result; };
  reader.readAsText(file);
  return false;
}

async function handleUpload(options) {
  const formData = new FormData();
  formData.append('bookmark_file', options.file);
  try {
    const { data } = await request.post('/admin/api/bookmarks/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    previewData.value = data.data;
    message.success('解析成功');
  } catch (e) {
    message.error(e.response?.data?.error || '解析失败');
  }
  return false;
}

async function handleImport() {
  importing.value = true;
  try {
    const formData = new FormData();
    const blob = new Blob([bookmarkHtml.value], { type: 'text/html' });
    formData.append('bookmark_file', blob, 'bookmark.html');
    const { data } = await request.post('/admin/api/bookmarks/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      params: { skip_duplicate: skipDuplicates.value },
    });
    importResult.value = data;
    message.success('导入成功');
  } catch (e) {
    message.error(e.response?.data?.error || '导入失败');
  } finally {
    importing.value = false;
  }
}

function resetPreview() { previewData.value = null; importResult.value = null; }
function resetImport() { previewData.value = null; importResult.value = null; }
</script>

<style scoped>
.bookmark-card { overflow: hidden; }

.bookmark-title {
  padding: 16px 24px;
  font-size: 16px; font-weight: 600;
  color: var(--admin-card-foreground);
  border-bottom: 1px solid var(--admin-border-light);
  display: flex; align-items: center; gap: 8px;
}

.bookmark-body { padding: 24px; }

.upload-text {
  font-size: 16px; font-weight: 600;
  color: var(--admin-card-foreground);
  margin-bottom: 8px;
}

.upload-hint { font-size: 13px; color: var(--admin-muted-foreground); }

.preview-section { margin-top: 8px; }

.preview-stat {
  text-align: center; padding: 16px;
  background: var(--admin-muted); border-radius: var(--admin-radius);
}

.preview-stat-value {
  font-size: 32px; font-weight: 700; color: var(--admin-primary);
}

.preview-stat-label {
  font-size: 13px; color: var(--admin-muted-foreground); margin-top: 4px;
}

.folder-preview { margin-top: 16px; }
.folder-item { margin-bottom: 12px; }
.folder-name {
  font-weight: 600; font-size: 14px;
  color: var(--admin-card-foreground); margin-bottom: 6px;
}
.folder-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.more-hint { color: var(--admin-muted-foreground); font-size: 12px; align-self: center; }

.preview-actions {
  margin-top: 20px; display: flex;
  justify-content: flex-end; gap: 8px;
}
</style>
