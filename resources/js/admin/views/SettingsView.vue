<template>
  <div>
    <a-card title="系统设置" :bordered="false" :loading="loading">
      <a-tabs v-model:activeKey="activeTab">
        <!-- 基础信息 -->
        <a-tab-pane key="basic" tab="基础信息">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-form-item label="站点名称" required>
              <a-input v-model:value="form.site_name" placeholder="导航站名称" />
            </a-form-item>
            <a-form-item label="站点 Logo URL">
              <a-input v-model:value="form.site_logo" placeholder="Logo 图片地址（留空使用默认）" />
              <div v-if="form.site_logo" style="margin-top: 8px">
                <img :src="form.site_logo" alt="Logo 预览" style="max-height: 40px; border-radius: 4px; border: 1px solid #eee; padding: 4px" />
              </div>
            </a-form-item>
            <a-form-item label="站点描述">
              <a-textarea v-model:value="form.site_description" placeholder="站点描述（用于 SEO）" :rows="2" />
            </a-form-item>
            <a-form-item label="站点关键词">
              <a-input v-model:value="form.site_keywords" placeholder="多个关键词用逗号分隔" />
            </a-form-item>
            <a-form-item label="页脚文字">
              <a-textarea v-model:value="form.footer_text" placeholder="页脚版权信息（可选）" :rows="2" />
            </a-form-item>
            <a-form-item label="ICP 备案号">
              <a-input v-model:value="form.icp_number" placeholder="如：京ICP备XXXXXXXX号" />
            </a-form-item>
          </a-form>
        </a-tab-pane>

        <!-- 功能开关 -->
        <a-tab-pane key="features" tab="功能开关">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-form-item label="开放注册">
              <a-switch v-model:checked="form.enable_register" checked-children="开放" un-checked-children="关闭" />
              <div style="color: #999; font-size: 12px; margin-top: 4px">关闭后新用户将无法注册账号</div>
            </a-form-item>
            <a-form-item label="维护模式">
              <a-switch v-model:checked="form.maintenance_mode" checked-children="开启" un-checked-children="关闭" />
              <div style="color: #999; font-size: 12px; margin-top: 4px">开启后前台将显示维护页面，后台仍可正常访问</div>
            </a-form-item>
          </a-form>
        </a-tab-pane>

        <!-- 首页背景 -->
        <a-tab-pane key="background" tab="首页背景">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-form-item label="背景类型">
              <a-radio-group v-model:value="form.home_background_type">
                <a-radio value="none">默认（跟随主题）</a-radio>
                <a-radio value="color">自定义纯色</a-radio>
                <a-radio value="image">自定义图片</a-radio>
              </a-radio-group>
            </a-form-item>
            <a-form-item v-if="form.home_background_type === 'color'" label="背景颜色">
              <div style="display: flex; align-items: center; gap: 8px">
                <input type="color" v-model="form.home_background_color" style="width: 40px; height: 32px; border: 1px solid #d9d9d9; border-radius: 4px; cursor: pointer; padding: 2px" />
                <a-input v-model:value="form.home_background_color" placeholder="#f5f5f5" style="width: 160px" />
              </div>
              <div style="color: #999; font-size: 12px; margin-top: 4px">建议使用浅色系，避免影响内容可读性</div>
            </a-form-item>
            <a-form-item v-if="form.home_background_type === 'image'" label="背景图片">
              <div style="display: flex; align-items: flex-start; gap: 8px">
                <a-input v-model:value="form.home_background_image" placeholder="输入图片 URL 或上传" style="flex: 1" />
                <a-upload :show-upload-list="false" accept="image/*" :before-upload="handleBgUpload">
                  <a-button :loading="bgUploading">上传</a-button>
                </a-upload>
              </div>
              <div v-if="form.home_background_image" style="margin-top: 8px">
                <img :src="form.home_background_image" alt="背景预览" style="max-width: 320px; max-height: 160px; border-radius: 6px; border: 1px solid #eee" />
              </div>
              <div style="color: #999; font-size: 12px; margin-top: 4px">图片会自动铺满并居中，暗色模式下会叠加半透明遮罩</div>
            </a-form-item>
          </a-form>
        </a-tab-pane>

        <!-- SEO 设置 -->
        <a-tab-pane key="seo" tab="SEO 设置">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-divider orientation="left">搜索引擎验证</a-divider>
            <a-form-item label="百度站长验证码">
              <a-input v-model:value="form.baidu_verify" placeholder="百度资源平台提供的验证码" />
              <div style="color: #999; font-size: 12px; margin-top: 4px">
                前往 <a href="https://ziyuan.baidu.com" target="_blank">ziyuan.baidu.com</a> → 添加网站 → HTML 标签验证，复制 content 值填入
              </div>
            </a-form-item>
            <a-form-item label="Google Search Console 验证码">
              <a-input v-model:value="form.google_verify" placeholder="Google 提供的验证码" />
              <div style="color: #999; font-size: 12px; margin-top: 4px">
                前往 <a href="https://search.google.com/search-console" target="_blank">Search Console</a> → 添加资源 → HTML 标签，复制 content 值填入
              </div>
            </a-form-item>
            <a-form-item label="Bing Webmaster 验证码">
              <a-input v-model:value="form.bing_verify" placeholder="Bing 提供的验证码" />
              <div style="color: #999; font-size: 12px; margin-top: 4px">
                前往 <a href="https://www.bing.com/webmasters" target="_blank">Bing Webmaster</a> → 添加站点 → HTML 标签，复制 content 值填入
              </div>
            </a-form-item>

            <a-divider orientation="left">SEO 信息</a-divider>
            <a-alert type="info" show-icon style="margin-bottom: 16px">
              <template #message>
                系统已自动配置：sitemap.xml、robots.txt、Open Graph 标签、JSON-LD 结构化数据、百度/Bing 等搜索引擎预渲染
              </template>
            </a-alert>
            <div style="display: flex; gap: 12px; margin-bottom: 16px">
              <a-button @click="openUrl('/sitemap.xml')">查看 Sitemap</a-button>
              <a-button @click="openUrl('/robots.txt')">查看 Robots.txt</a-button>
            </div>
          </a-form>
        </a-tab-pane>

        <!-- 公告管理 -->
        <a-tab-pane key="announcement" tab="公告管理">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-form-item label="首页公告">
              <a-textarea v-model:value="form.announcement" placeholder="留空则不显示公告栏" :rows="4" />
              <div style="color: #999; font-size: 12px; margin-top: 4px">公告将显示在前台首页顶部，支持 HTML 标签</div>
            </a-form-item>
            <a-form-item label="公告预览" v-if="form.announcement">
              <div style="background: #fffbe6; border: 1px solid #ffe58f; border-radius: 6px; padding: 12px 16px; color: #d48806">
                <span v-html="sanitizedAnnouncement"></span>
              </div>
            </a-form-item>
          </a-form>
        </a-tab-pane>

        <!-- 友链管理 -->
        <a-tab-pane key="links" tab="友链管理">
          <div style="margin-top: 16px">
            <div style="margin-bottom: 12px; text-align: right">
              <a-button type="primary" @click="openLinkModal()">
                <template #icon><PlusOutlined /></template>
                添加友链
              </a-button>
            </div>
            <a-table :data-source="friendLinks" :columns="linkColumns" :pagination="false" row-key="id" size="small">
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'name'">
                  <div style="display: flex; align-items: center; gap: 8px">
                    <img v-if="record.logo" :src="record.logo" style="width: 20px; height: 20px; border-radius: 2px; object-fit: cover" />
                    <span>{{ record.name }}</span>
                  </div>
                </template>
                <template v-if="column.key === 'url'">
                  <a :href="record.url" target="_blank" rel="noopener" style="color: #1677ff; max-width: 260px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ record.url }}</a>
                </template>
                <template v-if="column.key === 'is_active'">
                  <a-tag :color="record.is_active ? 'green' : 'default'">{{ record.is_active ? '显示' : '隐藏' }}</a-tag>
                </template>
                <template v-if="column.key === 'actions'">
                  <a-space>
                    <a @click="openLinkModal(record)">编辑</a>
                    <a-popconfirm title="确认删除？" @confirm="deleteLink(record.id)">
                      <a style="color: #ff4d4f">删除</a>
                    </a-popconfirm>
                  </a-space>
                </template>
              </template>
            </a-table>
          </div>
        </a-tab-pane>

        <!-- 二维码管理 -->
        <a-tab-pane key="qrcode" tab="二维码管理">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-row :gutter="24">
              <a-col :span="12">
                <a-form-item label="二维码 1">
                  <div style="display: flex; align-items: flex-start; gap: 8px">
                    <a-input v-model:value="form.qrcode_1_image" placeholder="上传或手动填写" style="flex: 1" />
                    <a-upload :show-upload-list="false" accept="image/*" :before-upload="(f) => handleQrcodeUpload(f, 'qrcode_1_image')">
                      <a-button :loading="qrcodeUploading === 'qrcode_1_image'">上传</a-button>
                    </a-upload>
                  </div>
                  <div v-if="form.qrcode_1_image" style="margin-top: 8px">
                    <img :src="form.qrcode_1_image" alt="预览" style="max-width: 120px; border: 1px solid #eee; border-radius: 4px; padding: 4px" />
                  </div>
                </a-form-item>
                <a-form-item label="说明文字">
                  <a-input v-model:value="form.qrcode_1_label" placeholder="如：微信公众号" />
                </a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="二维码 2">
                  <div style="display: flex; align-items: flex-start; gap: 8px">
                    <a-input v-model:value="form.qrcode_2_image" placeholder="上传或手动填写" style="flex: 1" />
                    <a-upload :show-upload-list="false" accept="image/*" :before-upload="(f) => handleQrcodeUpload(f, 'qrcode_2_image')">
                      <a-button :loading="qrcodeUploading === 'qrcode_2_image'">上传</a-button>
                    </a-upload>
                  </div>
                  <div v-if="form.qrcode_2_image" style="margin-top: 8px">
                    <img :src="form.qrcode_2_image" alt="预览" style="max-width: 120px; border: 1px solid #eee; border-radius: 4px; padding: 4px" />
                  </div>
                </a-form-item>
                <a-form-item label="说明文字">
                  <a-input v-model:value="form.qrcode_2_label" placeholder="如：微信小程序" />
                </a-form-item>
              </a-col>
            </a-row>
          </a-form>
        </a-tab-pane>

        <!-- 关于页面管理 -->
        <a-tab-pane key="about" tab="关于页面">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-form-item label="关于描述">
              <a-textarea v-model:value="form.about_description" placeholder="显示在关于页 Banner 的描述文字" :rows="3" />
            </a-form-item>

            <a-form-item label="发展历程（时间轴）">
              <div style="margin-bottom: 8px; color: #999; font-size: 12px">每行格式：日期 | 标题 | 描述</div>
              <div v-for="(item, idx) in timelineItems" :key="idx" style="display: flex; gap: 8px; margin-bottom: 8px; align-items: flex-start">
                <a-input v-model:value="item.date" placeholder="2026-04-01" style="width: 130px; flex-shrink: 0" />
                <a-input v-model:value="item.title" placeholder="标题" style="flex: 1" />
                <a-input v-model:value="item.description" placeholder="描述" style="flex: 1" />
                <a-button danger size="small" @click="timelineItems.splice(idx, 1)">删除</a-button>
              </div>
              <a-button type="dashed" block @click="timelineItems.push({ date: '', title: '', description: '' })">+ 添加事件</a-button>
            </a-form-item>

            <a-divider>联系信息</a-divider>
            <a-row :gutter="16">
              <a-col :span="8">
                <a-form-item label="联系邮箱">
                  <a-input v-model:value="form.contact_email" placeholder="admin@example.com" />
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item label="QQ">
                  <a-input v-model:value="form.contact_qq" placeholder="QQ 号" />
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item label="微信">
                  <a-input v-model:value="form.contact_wechat" placeholder="微信号" />
                </a-form-item>
              </a-col>
            </a-row>

            <a-divider>免责声明 / 用户协议</a-divider>
            <a-form-item label="免责声明内容">
              <a-textarea v-model:value="form.terms_content" placeholder="支持 HTML 标签" :rows="8" />
            </a-form-item>
          </a-form>
        </a-tab-pane>

        <!-- 邮件配置 -->
        <a-tab-pane key="mail" tab="邮件配置">
          <a-form :model="form" layout="vertical" style="margin-top: 16px">
            <a-form-item label="邮箱预设">
              <a-select v-model:value="selectedPreset" :options="presetOptions" style="width: 200px" @change="applyMailPreset">
              </a-select>
              <div style="color: #999; font-size: 12px; margin-top: 4px">选择邮箱类型自动填充 SMTP 地址和端口</div>
            </a-form-item>

            <a-row :gutter="16">
              <a-col :span="16">
                <a-form-item label="SMTP 服务器" required>
                  <a-input v-model:value="form.mail_host" placeholder="如 smtp.qq.com" />
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item label="端口" required>
                  <a-input-number v-model:value="form.mail_port" :min="1" :max="65535" style="width: 100%" />
                </a-form-item>
              </a-col>
            </a-row>

            <a-row :gutter="16">
              <a-col :span="8">
                <a-form-item label="加密方式" required>
                  <a-select v-model:value="form.mail_encryption">
                    <a-select-option value="ssl">SSL</a-select-option>
                    <a-select-option value="tls">TLS</a-select-option>
                    <a-select-option value="null">无加密</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item label="用户名" required>
                  <a-input v-model:value="form.mail_username" placeholder="邮箱地址" @blur="onMailUsernameBlur" />
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item label="授权码 / 密码" required>
                  <a-input-password v-model:value="form.mail_password" :placeholder="form.mail_password ? '已保存，留空不修改' : '请输入授权码'" />
                </a-form-item>
              </a-col>
            </a-row>

            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item label="发件人地址">
                  <a-input v-model:value="form.mail_from_address" placeholder="留空则使用用户名" />
                </a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="发件人名称">
                  <a-input v-model:value="form.mail_from_name" placeholder="留空则使用站点名称" />
                </a-form-item>
              </a-col>
            </a-row>

            <a-divider>发送测试</a-divider>

            <a-form-item label="测试收件邮箱">
              <div style="display: flex; gap: 8px">
                <a-input v-model:value="testEmailAddress" placeholder="输入邮箱地址" style="flex: 1" />
                <a-button type="primary" :loading="testEmailSending" @click="sendTestEmail" :disabled="!form.mail_host">
                  发送测试邮件
                </a-button>
              </div>
              <div style="color: #999; font-size: 12px; margin-top: 4px">
                保存设置后，可发送测试邮件验证配置是否正确。QQ/163 等邮箱需要使用授权码而非登录密码。
              </div>
            </a-form-item>
          </a-form>
        </a-tab-pane>

      </a-tabs>

      <div v-if="activeTab !== 'links'" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0f0f0; text-align: right">
        <a-button type="primary" @click="handleSave" :loading="saving">保存设置</a-button>
      </div>
    </a-card>

    <!-- Friend Link Modal -->
    <a-modal v-model:open="linkModalVisible" :title="editingLink ? '编辑友链' : '添加友链'" @ok="saveLink" :confirm-loading="linkSaving" width="480px">
      <a-form :model="linkForm" layout="vertical" style="margin-top: 16px">
        <a-form-item label="站点名称" required>
          <a-input v-model:value="linkForm.name" placeholder="友链站点名称" />
        </a-form-item>
        <a-form-item label="站点 URL" required>
          <a-input v-model:value="linkForm.url" placeholder="https://example.com" />
        </a-form-item>
        <a-form-item label="Logo URL">
          <a-input v-model:value="linkForm.logo" placeholder="站点 Logo 图片地址（可选）" />
        </a-form-item>
        <a-form-item label="排序">
          <a-input-number v-model:value="linkForm.sort_order" :min="0" :max="999" style="width: 100%" />
        </a-form-item>
        <a-form-item label="显示状态">
          <a-switch v-model:checked="linkForm.is_active" checked-children="显示" un-checked-children="隐藏" />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue';
import { message } from 'antdv-next';
import { PlusOutlined } from '@ant-design/icons-vue';
import request from '../utils/request';
import { sanitizeHtml } from '../../composables/useSanitize';

const loading = ref(false);
const saving = ref(false);
const activeTab = ref('basic');
const sanitizedAnnouncement = computed(() => sanitizeHtml(form.announcement));
const form = reactive({
  site_name: '',
  site_description: '',
  site_keywords: '',
  site_logo: '',
  footer_text: '',
  icp_number: '',
  enable_register: true,
  maintenance_mode: false,
  announcement: '',
  qrcode_1_image: '',
  qrcode_1_label: '',
  qrcode_2_image: '',
  qrcode_2_label: '',
  about_description: '',
  about_timeline: '[]',
  terms_content: '',
  contact_email: '',
  contact_qq: '',
  contact_wechat: '',
  mail_host: '',
  mail_port: 465,
  mail_encryption: 'ssl',
  mail_username: '',
  mail_password: '',
  mail_from_address: '',
  mail_from_name: '',
  baidu_verify: '',
  google_verify: '',
  bing_verify: '',
  home_background_type: 'none',
  home_background_color: '#f5f5f5',
  home_background_image: '',
});

// Timeline editor
const timelineItems = ref([]);
try {
  timelineItems.value = JSON.parse(form.about_timeline || '[]');
} catch { timelineItems.value = []; }
watch(timelineItems, (val) => {
  form.about_timeline = JSON.stringify(val);
}, { deep: true });

const qrcodeUploading = ref('');
const bgUploading = ref(false);

async function handleBgUpload(file) {
  bgUploading.value = true;
  try {
    const formData = new FormData();
    formData.append('image', file);
    const { data } = await request.post('/admin/api/settings/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (data.code === 0) {
      form.home_background_image = data.data.url;
      message.success('上传成功');
    }
  } catch (e) {
    message.error(e.response?.data?.message || '上传失败');
  } finally {
    bgUploading.value = false;
  }
  return false;
}

// Mail presets
const mailPresets = [
  { label: '自定义', host: '', port: 465, encryption: 'ssl' },
  { label: 'QQ 邮箱', host: 'smtp.qq.com', port: 465, encryption: 'ssl' },
  { label: '163 邮箱', host: 'smtp.163.com', port: 465, encryption: 'ssl' },
  { label: '126 邮箱', host: 'smtp.126.com', port: 465, encryption: 'ssl' },
  { label: 'Gmail', host: 'smtp.gmail.com', port: 587, encryption: 'tls' },
  { label: 'Outlook', host: 'smtp.office365.com', port: 587, encryption: 'tls' },
  { label: '阿里企业邮', host: 'smtp.qiye.aliyun.com', port: 465, encryption: 'ssl' },
  { label: '腾讯企业邮', host: 'smtp.exmail.qq.com', port: 465, encryption: 'ssl' },
  { label: 'Coremail', host: '', port: 465, encryption: 'ssl' },
];

const selectedPreset = ref('0');
const testEmailAddress = ref('');
const testEmailSending = ref(false);

const presetOptions = computed(() =>
  mailPresets.map((p, idx) => ({ value: String(idx), label: p.label }))
);

function applyMailPreset(idx) {
  selectedPreset.value = String(idx);
  const preset = mailPresets[idx];
  if (preset.host) {
    form.mail_host = preset.host;
  }
  form.mail_port = preset.port;
  form.mail_encryption = preset.encryption;
}

async function sendTestEmail() {
  if (!testEmailAddress.value) {
    message.warning('请输入测试收件邮箱');
    return;
  }
  testEmailSending.value = true;
  try {
    const { data } = await request.post('/admin/api/settings/test-email', {
      to: testEmailAddress.value,
      mail_host: form.mail_host,
      mail_port: form.mail_port,
      mail_encryption: form.mail_encryption,
      mail_username: form.mail_username,
      mail_password: form.mail_password,
      mail_from_address: form.mail_from_address,
      mail_from_name: form.mail_from_name,
    });
    if (data.code === 0) {
      message.success(data.msg);
    } else {
      message.error(data.msg);
    }
  } catch (e) {
    message.error(e.response?.data?.msg || '发送失败');
  } finally {
    testEmailSending.value = false;
  }
}

function onMailUsernameBlur() {
  if (!form.mail_from_address && form.mail_username) {
    form.mail_from_address = form.mail_username;
  }
}

async function handleQrcodeUpload(file, field) {
  qrcodeUploading.value = field;
  try {
    const formData = new FormData();
    formData.append('image', file);
    const { data } = await request.post('/admin/api/settings/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (data.code === 0) {
      form[field] = data.data.url;
      message.success('上传成功');
    }
  } catch (e) {
    message.error(e.response?.data?.message || '上传失败');
  } finally {
    qrcodeUploading.value = '';
  }
  return false;
}

// Friend links
const friendLinks = ref([]);
const linkModalVisible = ref(false);
const linkSaving = ref(false);
const editingLink = ref(null);
const linkForm = reactive({
  name: '',
  url: '',
  logo: '',
  sort_order: 0,
  is_active: true,
});

const linkColumns = [
  { title: '名称', key: 'name', dataIndex: 'name' },
  { title: 'URL', key: 'url' },
  { title: '排序', key: 'sort_order', dataIndex: 'sort_order', width: 70 },
  { title: '状态', key: 'is_active', width: 80 },
  { title: '操作', key: 'actions', width: 120 },
];

onMounted(async () => {
  loading.value = true;
  try {
    const { data } = await request.get('/admin/api/settings');
    Object.assign(form, data.data);
    // Parse timeline JSON for editor
    try {
      timelineItems.value = JSON.parse(form.about_timeline || '[]');
    } catch { timelineItems.value = []; }
  } finally {
    loading.value = false;
  }
  fetchFriendLinks();
});

async function fetchFriendLinks() {
  try {
    const { data } = await request.get('/admin/api/friend-links');
    friendLinks.value = data.data || [];
  } catch {
    // ignore
  }
}

function openLinkModal(record = null) {
  editingLink.value = record;
  if (record) {
    Object.assign(linkForm, { ...record });
  } else {
    Object.assign(linkForm, { name: '', url: '', logo: '', sort_order: 0, is_active: true });
  }
  linkModalVisible.value = true;
}

async function saveLink() {
  if (!linkForm.name || !linkForm.url) {
    message.warning('请填写站点名称和 URL');
    return;
  }
  linkSaving.value = true;
  try {
    if (editingLink.value) {
      await request.put(`/admin/api/friend-links/${editingLink.value.id}`, { ...linkForm });
      message.success('更新成功');
    } else {
      await request.post('/admin/api/friend-links', { ...linkForm });
      message.success('添加成功');
    }
    linkModalVisible.value = false;
    fetchFriendLinks();
  } catch (e) {
    message.error(e.response?.data?.message || '操作失败');
  } finally {
    linkSaving.value = false;
  }
}

async function deleteLink(id) {
  try {
    await request.delete(`/admin/api/friend-links/${id}`);
    message.success('删除成功');
    fetchFriendLinks();
  } catch (e) {
    message.error(e.response?.data?.message || '删除失败');
  }
}

async function handleSave() {
  saving.value = true;
  try {
    await request.put('/admin/api/settings', { ...form });
    message.success('设置已保存');
  } catch (e) {
    message.error(e.response?.data?.message || '保存失败');
  } finally {
    saving.value = false;
  }
}

function openUrl(path) {
  window.open(path, '_blank');
}
</script>
