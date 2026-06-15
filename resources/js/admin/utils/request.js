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
        const config = error.config || {};

        // The auth check (GET /admin/api/me) calls this with _silent401=true.
        // A 401 there just means "not logged in" — it's the expected state on
        // the login page, not an error. Resolve to a null user so axios doesn't
        // log a red "401 Unauthorized" line to the console every page load.
        if (status === 401 && config._silent401) {
            return Promise.resolve({ data: null });
        }

        if (status === 401 || status === 419) {
            if (!window.location.pathname.startsWith('/admin/login')) {
                window.location.href = '/admin/login';
            }
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
