import { defineStore } from 'pinia';
import request from '../utils/request';

export const useSiteSettingsStore = defineStore('siteSettings', {
    state: () => ({
        settings: {
            site_name: '',
            site_description: '',
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
            home_background_type: 'none',
            home_background_color: '',
            home_background_image: '',
        },
        loaded: false,
    }),

    getters: {
        siteName: (state) => state.settings.site_name || document.title || 'NavMate',
        hasAnnouncement: (state) => !!state.settings.announcement,
        backgroundStyle: (state) => {
            const type = state.settings.home_background_type;
            if (type === 'color' && state.settings.home_background_color) {
                return { backgroundColor: state.settings.home_background_color };
            }
            if (type === 'image' && state.settings.home_background_image) {
                return {
                    backgroundImage: `url(${state.settings.home_background_image})`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                    backgroundAttachment: 'fixed',
                    backgroundRepeat: 'no-repeat',
                };
            }
            return null;
        },
    },

    actions: {
        async fetchSettings() {
            try {
                const { data } = await request.get('/api/settings');
                this.settings = data;
                this.loaded = true;

                // Update meta description
                if (data.site_description) {
                    const meta = document.querySelector('meta[name="description"]');
                    if (meta) meta.setAttribute('content', data.site_description);
                }
            } catch {
                // Settings not available, use defaults
            }
        },
    },
});
