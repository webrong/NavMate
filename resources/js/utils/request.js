import axios from 'axios';

const request = axios.create({
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
});

// CSRF token
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
    request.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.content;
}

// Response interceptor — global error handling
request.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        if (status === 401 || status === 419) {
            import('../stores/auth').then(({ useAuthStore }) => {
                const auth = useAuthStore();
                auth.user = null;
            });
            import('../stores/toast').then(({ useToastStore }) => {
                const toast = useToastStore();
                toast.error('登录已过期，请重新登录');
            });
            if (window.location.pathname !== '/') {
                window.location.href = '/?login=true';
            }
            return Promise.reject(error);
        }

        if (!error.response) {
            import('../stores/toast').then(({ useToastStore }) => {
                const toast = useToastStore();
                toast.error('网络连接失败，请检查网络');
            });
            return Promise.reject(error);
        }

        if (status >= 500) {
            import('../stores/toast').then(({ useToastStore }) => {
                const toast = useToastStore();
                toast.error('服务器错误，请稍后重试');
            });
            return Promise.reject(error);
        }

        return Promise.reject(error);
    }
);

export default request;
