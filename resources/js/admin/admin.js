import { createApp } from 'vue';
import { createPinia } from 'pinia';
import Antd from 'antdv-next';
import 'antdv-next/dist/reset.css';
import '../../css/admin.scss';
import router from './router';
import App from './App.vue';

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.use(Antd);
app.mount('#admin-app');
