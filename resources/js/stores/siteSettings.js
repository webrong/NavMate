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
        // Auto-invalidate stale settings so admin changes eventually show up.
        lastFetchedAt: 0,
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
        async fetchSettings(force = false) {
            // Skip if we have fresh data and the caller didn't force a refresh.
            // Prevents a stale admin change from being invisible to long-lived
            // front-end sessions.
            if (!force && this.loaded && Date.now() - this.lastFetchedAt < 5 * 60 * 1000) {
                return;
            }
            try {
                const { data } = await request.get('/api/settings');
                this.settings = data;
                this.loaded = true;
                this.lastFetchedAt = Date.now();

                // Update meta description
                if (data.site_description) {
                    const meta = document.querySelector('meta[name="description"]');
                    if (meta) meta.setAttribute('content', data.site_description);
                }
            } catch {
                // Settings not available, use defaults
            }
        },

        /** Force-refresh settings on next fetchSettings call. */
        invalidate() {
            this.lastFetchedAt = 0;
        },
    },
});
