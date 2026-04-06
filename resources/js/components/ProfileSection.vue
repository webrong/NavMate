<template>
  <div class="content-area">
    <div class="content-section">
      <div class="section-header">
        <h2 class="section-title">个人资料</h2>
      </div>

      <!-- Avatar -->
      <div class="avatar-section">
        <div class="avatar-current">
          <img v-if="authStore.avatarUrl" :src="authStore.avatarUrl" alt="头像" class="avatar-img" />
          <span v-else class="avatar-letter">{{ authStore.userName ? authStore.userName.charAt(0).toUpperCase() : 'U' }}</span>
        </div>
        <div class="avatar-actions">
          <label class="profile-btn" :class="{ 'profile-btn-disabled': uploading }">
            {{ uploading ? '上传中...' : '更换头像' }}
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="avatar-file-input" @change="onAvatarChange" :disabled="uploading" />
          </label>
          <span class="avatar-hint">支持 JPG/PNG/GIF/WebP，最大 2MB</span>
        </div>
      </div>

      <div class="settings-list">
        <div class="settings-item">
          <span class="settings-item-label">用户名</span>
          <div class="profile-edit-row">
            <input v-if="editingName" v-model="name" type="text" class="profile-input" @keyup.enter="saveName" ref="nameInput" />
            <span v-else class="settings-item-value">{{ authStore.userName }}</span>
            <button v-if="editingName" class="profile-btn" @click="saveName" :disabled="saving">保存</button>
            <button v-if="editingName" class="profile-btn profile-btn-cancel" @click="editingName = false">取消</button>
            <button v-if="!editingName" class="profile-btn" @click="startEditName">修改</button>
          </div>
        </div>
        <div class="settings-item">
          <span class="settings-item-label">邮箱</span>
          <span class="settings-item-value">{{ authStore.user?.email }}</span>
        </div>
        <div class="settings-item">
          <span class="settings-item-label">密码</span>
          <div class="profile-edit-row">
            <button v-if="!editingPassword" class="profile-btn" @click="editingPassword = true">修改密码</button>
          </div>
        </div>
      </div>

      <!-- Password change form -->
      <div v-if="editingPassword" class="password-form">
        <div class="auth-field">
          <label>当前密码</label>
          <input v-model="currentPassword" type="password" placeholder="请输入当前密码" required autocomplete="current-password" />
        </div>
        <div class="auth-field">
          <label>新密码</label>
          <input v-model="newPassword" type="password" placeholder="至少8位" required autocomplete="new-password" />
          <PasswordStrength :password="newPassword" />
        </div>
        <div class="auth-field">
          <label>确认新密码</label>
          <input v-model="newPasswordConfirm" type="password" placeholder="再次输入新密码" required autocomplete="new-password" />
        </div>
        <div v-if="passwordError" class="auth-error">{{ passwordError }}</div>
        <div class="password-form-actions">
          <button class="auth-submit" style="width:auto;padding:0 24px" @click="savePassword" :disabled="saving">
            {{ saving ? '保存中...' : '保存密码' }}
          </button>
          <button class="profile-btn profile-btn-cancel" @click="cancelPasswordEdit">取消</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';
import PasswordStrength from './PasswordStrength.vue';

const authStore = useAuthStore();
const toast = useToastStore();

const name = ref(authStore.userName);
const editingName = ref(false);
const nameInput = ref(null);
const saving = ref(false);
const uploading = ref(false);

const editingPassword = ref(false);
const currentPassword = ref('');
const newPassword = ref('');
const newPasswordConfirm = ref('');
const passwordError = ref('');

async function onAvatarChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 2048 * 1024) {
    toast.error('图片不能超过 2MB');
    e.target.value = '';
    return;
  }
  uploading.value = true;
  const result = await authStore.uploadAvatar(file);
  uploading.value = false;
  if (result.success) {
    toast.success('头像已更新');
  } else {
    toast.error(result.message);
  }
  e.target.value = '';
}

function startEditName() {
  name.value = authStore.userName;
  editingName.value = true;
  nextTick(() => nameInput.value?.focus());
}

async function saveName() {
  if (!name.value.trim()) {
    toast.error('用户名不能为空');
    return;
  }
  saving.value = true;
  const result = await authStore.updateProfile({ name: name.value.trim() });
  saving.value = false;
  if (result.success) {
    editingName.value = false;
    toast.success('用户名已更新');
  } else {
    toast.error(result.message);
  }
}

function cancelPasswordEdit() {
  editingPassword.value = false;
  currentPassword.value = '';
  newPassword.value = '';
  newPasswordConfirm.value = '';
  passwordError.value = '';
}

async function savePassword() {
  passwordError.value = '';
  if (!currentPassword.value) {
    passwordError.value = '请输入当前密码';
    return;
  }
  if (newPassword.value.length < 8) {
    passwordError.value = '新密码至少8位';
    return;
  }
  if (newPassword.value !== newPasswordConfirm.value) {
    passwordError.value = '两次密码不一致';
    return;
  }
  saving.value = true;
  const result = await authStore.updateProfile({
    current_password: currentPassword.value,
    password: newPassword.value,
    password_confirmation: newPasswordConfirm.value,
  });
  saving.value = false;
  if (result.success) {
    toast.success('密码已更新');
    cancelPasswordEdit();
  } else {
    passwordError.value = result.message;
  }
}
</script>

<style scoped>
.avatar-section {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  margin-bottom: 4px;
}

.avatar-current {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--theme-color);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 600;
  overflow: hidden;
  flex-shrink: 0;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-letter {
  line-height: 1;
}

.avatar-actions {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.avatar-file-input {
  display: none;
}

.avatar-hint {
  font-size: 11px;
  color: var(--muted-color);
}

.profile-btn-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.settings-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 6px;
  transition: background 0.15s;
}

.settings-item:hover {
  background: var(--hover-bg-light);
}

.settings-item-label {
  font-size: 14px;
  color: var(--muted-color2);
  min-width: 64px;
}

.settings-item-value {
  flex: 1;
  font-size: 14px;
  color: var(--main-color);
}

.profile-edit-row {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-input {
  flex: 1;
  height: 32px;
  padding: 0 10px;
  border: 1px solid var(--input-border);
  border-radius: 6px;
  font-size: 14px;
  color: var(--main-color);
  background: var(--main-bg-color);
  outline: none;
  transition: border-color 0.15s;
}

.profile-input:focus {
  border-color: var(--theme-color);
}

.profile-btn {
  padding: 4px 12px;
  font-size: 12px;
  color: var(--theme-color);
  background: rgba(var(--theme-color-rgb), 0.08);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s;
  white-space: nowrap;
}

.profile-btn:hover:not(:disabled) {
  background: rgba(var(--theme-color-rgb), 0.16);
}

.profile-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.profile-btn-cancel {
  color: var(--muted-color);
  background: transparent;
}

.password-form {
  margin-top: 12px;
  padding: 16px;
  background: rgba(var(--theme-color-rgb), 0.03);
  border-radius: 8px;
  border: 1px solid rgba(var(--theme-color-rgb), 0.08);
}

.password-form-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
</style>
