import { createApp } from 'vue';
import { createPinia } from 'pinia';
import {
  // Named imports (not the full install plugin) so the bundler can
  // tree-shake antdv-next down to what the admin actually uses.
  Alert, Avatar, Breadcrumb, Button, Checkbox, Col, ConfigProvider, DateRangePicker,
  Divider, Drawer, Dropdown, Empty, Form, FormItem, Input, InputNumber, InputPassword,
  InputSearch, Layout, LayoutSider, Menu, MenuItem, Modal, Popconfirm, Progress, Radio,
  RadioGroup, Result, Row, Select, SelectOption, Skeleton, Space, Table, Tabs, TabPane,
  Tag, TextArea, Tooltip, Switch, TreeSelect, Upload, UploadDragger,
} from 'antdv-next';
import 'antdv-next/dist/reset.css';
import '../../css/admin.scss';
import router from './router';
import App from './App.vue';

const app = createApp(App);
app.use(createPinia());
app.use(router);

// Register with the exact kebab-case tag names used in templates.
// NOTE: when adding a new a-* tag to a view, register it here — Vue warns
// "Failed to resolve component" in the console if a tag is missing.
app
  .component('a-config-provider', ConfigProvider)
  .component('a-layout', Layout)
  .component('a-layout-sider', LayoutSider)
  .component('a-menu', Menu)
  .component('a-menu-item', MenuItem)
  .component('a-breadcrumb', Breadcrumb)
  .component('a-form', Form)
  .component('a-form-item', FormItem)
  .component('a-input', Input)
  .component('a-input-search', InputSearch)
  .component('a-input-password', InputPassword)
  .component('a-input-number', InputNumber)
  .component('a-textarea', TextArea)
  .component('a-button', Button)
  .component('a-row', Row)
  .component('a-col', Col)
  .component('a-tag', Tag)
  .component('a-select', Select)
  .component('a-select-option', SelectOption)
  .component('a-table', Table)
  .component('a-tooltip', Tooltip)
  .component('a-tabs', Tabs)
  .component('a-tab-pane', TabPane)
  .component('a-switch', Switch)
  .component('a-radio', Radio)
  .component('a-radio-group', RadioGroup)
  .component('a-divider', Divider)
  .component('a-popconfirm', Popconfirm)
  .component('a-empty', Empty)
  .component('a-alert', Alert)
  .component('a-upload', Upload)
  .component('a-upload-dragger', UploadDragger)
  .component('a-space', Space)
  .component('a-modal', Modal)
  .component('a-skeleton', Skeleton)
  .component('a-checkbox', Checkbox)
  .component('a-result', Result)
  .component('a-range-picker', DateRangePicker)
  .component('a-progress', Progress)
  .component('a-drawer', Drawer)
  .component('a-avatar', Avatar)
  .component('a-dropdown', Dropdown)
  .component('a-tree-select', TreeSelect);

app.mount('#admin-app');
