import axios from 'axios';

const request = axios.create({
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
});

// CSRF token — read per-request so session rotation is picked up
// instead of freezing whatever the meta tag held at module load.
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

request.interceptors.request.use((config) => {
    config.headers['X-CSRF-TOKEN'] = csrfToken();
    return config;
});

// Response interceptor — global error handling

// Single-flight guard for 401/419: when a session expires several in-flight
// requests fail at once, but only the first should toast and redirect.
let redirecting401 = false;
request.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        if (status === 401 || status === 419) {
            if (redirecting401) {
                return Promise.reject(error);
            }
            redirecting401 = true;
            setTimeout(() => {
                redirecting401 = false;
            }, 2000);

            import('../stores/auth').then(({ useAuthStore }) => {
                const auth = useAuthStore();
                auth.user = null;
            });
            import('../stores/toast').then(({ useToastStore }) => {
                const toast = useToastStore();
                toast.error('登录已过期，请重新登录');
            });
            import('../router').then(({ default: router }) => {
                if (window.location.pathname !== '/') {
                    router.push({ path: '/', query: { login: 'true' } });
                }
            });
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
