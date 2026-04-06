<template>
  <div style="max-width: 600px; margin: 0 auto">
    <a-card title="书签导入" :bordered="false">
      <div v-if="!previewData && !importResult">
        <a-upload-dragger
          name="bookmark_file"
          :accept="['.html', '.htm']"
          :max-count="1"
          :before-upload="beforeUpload"
          :customRequest="handleUpload"
        >
          <p class="ant-upload-drag-icon-text">点击或拖拽上传书签文件</p>
          <p class="ant-upload-hint">支持 Chrome/Firefox/Edge 导出的 HTML 书签文件</p>
        </a-upload-dragger>
      </div>

      <!-- Preview -->
      <div v-if="previewData" style="margin-top: 24px">
        <a-divider>预览结果</a-divider>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-statistic title="书签数" :value="previewData.total_bookmarks || 0" />
          </a-col>
          <a-col :span="12">
            <a-statistic title="文件夹" :value="previewData.total_folders || 0" />
          </a-col>
        </a-row>
        <a-checkbox v-model:checked="skipDuplicates" style="margin: 12px 0">跳过重复书签</a-checkbox>
        <div style="margin-top: 12px">
          <div v-for="folder in previewData.folders" :key="folder.name" style="margin-bottom: 8px">
            <div style="font-weight: 600; margin-bottom: 4px">{{ folder.name }} ({{ folder.children ? folder.children.length : 0 }})</div>
            <a-tag v-for="item in (folder.children || []).slice(0, 5)" :key="item.title" style="margin: 2px">{{ item.title }}</a-tag>
            <span v-if="(folder.children || []).length > 5" style="color: #999; font-size: 12px">...等</span>
          </div>
        </div>
        <div style="margin-top: 16px; text-align: right">
          <a-button @click="resetPreview" style="margin-right: 8px">重新选择</a-button>
          <a-button type="primary" :loading="importing" :disabled="!previewData" @click="handleImport">开始导入</a-button>
        </div>
      </div>

      <!-- Result -->
      <a-result v-if="importResult" status="success" title="导入完成" :sub-title="importResult.message">
        <template #extra>
          <a-button type="primary" @click="resetImport">继续导入</a-button>
        </template>
      </a-result>
    </a-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { message } from 'antdv-next';
import request from '../utils/request';

import { InboxOutlined } from '@ant-design/icons-vue';

const previewData = ref(null);
const importResult = ref(null)
const skipDuplicates = ref(true)
const importing = ref(false)
const bookmarkHtml = ref('')

function beforeUpload(file) {
  const reader = new FileReader()
  reader.onload = (e) => {
    bookmarkHtml.value = e.target.result
  }
  reader.readAsText(file)
  return false
}

async function handleUpload(options) {
  const formData = new FormData()
  formData.append('bookmark_file', options.file)
  try {
    const { data } = await request.post('/admin/api/bookmarks/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    previewData.value = data.data
    message.success('解析成功')
  } catch (e) {
    message.error(e.response?.data?.error || '解析失败')
  }
  return false
}

async function handleImport() {
  importing.value = true
  try {
    const formData = new FormData()
    const blob = new Blob([bookmarkHtml.value], { type: 'text/html' })
    formData.append('bookmark_file', blob, 'bookmark.html')
    const { data } = await request.post('/admin/api/bookmarks/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      params: { skip_duplicate: skipDuplicates.value },
    })
    importResult.value = data
    message.success('导入成功')
  } catch (e) {
    message.error(e.response?.data?.error || '导入失败')
  } finally {
    importing.value = false
  }
}
function resetPreview() {
  previewData.value = null
  importResult.value = null
}
function resetImport() {
  previewData.value = null
  importResult.value = null
}
</script>
