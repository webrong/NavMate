import axios from 'axios';
import { message } from 'antdv-next';

const request = axios.create({
    baseURL: '/',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
});

// CSRF token
const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    request.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Response interceptor
request.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        if (status === 401 || status === 419) {
            window.location.href = '/admin/login';
            return Promise.reject(error);
        }
        if (status === 403) {
            message.error('无权限执行此操作');
        } else if (status === 422) {
            const msg = error.response?.data?.message || '验证失败';
            message.error(msg);
        } else {
            message.error(error.response?.data?.error || error.response?.data?.message || '请求失败');
        }
        return Promise.reject(error);
    }
);

export default request;
