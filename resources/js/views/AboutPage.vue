<template>
  <div class="about-page">
    <!-- Hero Banner -->
    <div class="about-banner">
      <div class="banner-orb banner-orb-1"></div>
      <div class="banner-orb banner-orb-2"></div>
      <div class="banner-orb banner-orb-3"></div>
      <div class="about-banner-content">
        <h1 class="about-site-name">{{ siteSettings.siteName }}</h1>
        <p class="about-desc">{{ siteSettings.settings.about_description || '上班人必备的职场办公导航网站' }}</p>
        <div class="about-stats">
          <div class="about-stat-item">
            <div class="about-stat-num">{{ stats.categories }}</div>
            <div class="about-stat-label">分类</div>
          </div>
          <div class="about-stat-item">
            <div class="about-stat-num">{{ stats.sites }}</div>
            <div class="about-stat-label">站点</div>
          </div>
          <div class="about-stat-item">
            <div class="about-stat-num">{{ stats.months }}</div>
            <div class="about-stat-label">月运营</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline Section -->
    <div class="about-section" v-if="timeline.length">
      <h2 class="about-section-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        发展历程
      </h2>
      <div class="timeline">
        <div v-for="(item, idx) in timeline" :key="idx" class="timeline-item" :class="{ 'timeline-left': idx % 2 === 0, 'timeline-right': idx % 2 !== 0 }">
          <div class="timeline-dot"></div>
          <div class="timeline-card">
            <div class="timeline-date">{{ item.date }}</div>
            <div class="timeline-title">{{ item.title }}</div>
            <div class="timeline-desc" v-if="item.description">{{ item.description }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Section -->
    <div class="about-section" v-if="hasContact">
      <h2 class="about-section-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        联系我们
      </h2>
      <div class="contact-grid">
        <div class="contact-item" v-if="siteSettings.settings.contact_email">
          <span class="contact-icon">📧</span>
          <a :href="'mailto:' + siteSettings.settings.contact_email">{{ siteSettings.settings.contact_email }}</a>
        </div>
        <div class="contact-item" v-if="siteSettings.settings.contact_qq">
          <span class="contact-icon">💬</span>
          <span>QQ: {{ siteSettings.settings.contact_qq }}</span>
        </div>
        <div class="contact-item" v-if="siteSettings.settings.contact_wechat">
          <span class="contact-icon">💬</span>
          <span>微信: {{ siteSettings.settings.contact_wechat }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useSiteSettingsStore } from '../stores/siteSettings';
import { useCategoryStore } from '../stores/categories';

const siteSettings = useSiteSettingsStore();
const categoryStore = useCategoryStore();

const timeline = computed(() => {
  try {
    return JSON.parse(siteSettings.settings.about_timeline || '[]');
  } catch {
    return [];
  }
});

const hasContact = computed(() =>
  siteSettings.settings.contact_email || siteSettings.settings.contact_qq || siteSettings.settings.contact_wechat
);

const stats = computed(() => {
  const cats = categoryStore.categories || [];
  let siteCount = 0;
  const countSites = (items) => {
    for (const c of items) {
      if (c.sites) siteCount += c.sites.length;
      if (c.children) countSites(c.children);
    }
  };
  countSites(cats);

  let months = 0;
  if (timeline.value.length > 0) {
    const firstDate = new Date(timeline.value[timeline.value.length - 1].date);
    const now = new Date();
    months = Math.max(1, Math.round((now - firstDate) / (30.44 * 86400000)));
  }

  return { categories: cats.length, sites: siteCount, months };
});
</script>

<style scoped>
/* Banner — same gradient as search banner */
.about-banner {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 280px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  position: relative;
  overflow: hidden;
  padding: calc(var(--main-nav-height, 56px) + 40px) 20px 40px;
}

.about-banner::after {
  content: '';
  position: absolute;
  left: 0; right: 0; bottom: -1px;
  height: 100px;
  background: linear-gradient(to top, var(--body-bg-color) 0%, transparent 100%);
  pointer-events: none;
}

/* Orbs */
.banner-orb {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  z-index: 0;
  opacity: 0.4;
  filter: blur(40px);
}
.banner-orb-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, transparent 70%);
  top: -15%; left: 5%;
  animation: orb-float-1 18s ease-in-out infinite;
}
.banner-orb-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(255,200,100,0.5) 0%, transparent 70%);
  top: 30%; right: -5%;
  animation: orb-float-2 22s ease-in-out infinite;
}
.banner-orb-3 {
  width: 350px; height: 350px;
  background: radial-gradient(circle, rgba(150,220,255,0.55) 0%, transparent 70%);
  bottom: -20%; left: 40%;
  animation: orb-float-3 20s ease-in-out infinite;
}
@keyframes orb-float-1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(80px, -30px) scale(1.1); }
  50% { transform: translate(-40px, 50px) scale(0.9); }
  75% { transform: translate(60px, 20px) scale(1.05); }
}
@keyframes orb-float-2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(-60px, 40px) scale(1.15); }
  50% { transform: translate(50px, -50px) scale(0.95); }
  75% { transform: translate(-30px, -20px) scale(1.1); }
}
@keyframes orb-float-3 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(40px, 60px) scale(0.9); }
  50% { transform: translate(-70px, -20px) scale(1.1); }
  75% { transform: translate(30px, -40px) scale(1); }
}

.about-banner-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
}

.about-site-name {
  font-size: 32px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 12px;
  text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.about-desc {
  font-size: 16px;
  color: rgba(255,255,255,0.85);
  margin: 0 0 28px;
  line-height: 1.6;
}

.about-stats {
  display: flex;
  justify-content: center;
  gap: 32px;
}

.about-stat-item {
  background: rgba(255,255,255,0.15);
  border-radius: 12px;
  padding: 12px 24px;
  backdrop-filter: blur(10px);
}

.about-stat-num {
  font-size: 28px;
  font-weight: 700;
  color: #fff;
}

.about-stat-label {
  font-size: 13px;
  color: rgba(255,255,255,0.7);
  margin-top: 2px;
}

/* Section */
.about-section {
  max-width: 900px;
  margin: 32px auto 0;
  padding: 0 16px;
}

.about-section-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--main-color);
  margin: 0 0 24px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Timeline */
.timeline {
  position: relative;
  padding: 8px 0;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 50%;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(180deg, var(--theme-color), rgba(var(--theme-color-rgb), 0.2));
  transform: translateX(-50%);
}

.timeline-item {
  position: relative;
  width: 50%;
  padding-bottom: 24px;
}

.timeline-item.timeline-left {
  padding-right: 40px;
  text-align: right;
}

.timeline-item.timeline-right {
  margin-left: 50%;
  padding-left: 40px;
}

.timeline-dot {
  position: absolute;
  top: 6px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--theme-color);
  border: 3px solid var(--main-bg-color);
  box-shadow: 0 0 0 2px var(--theme-color);
  z-index: 1;
}

.timeline-left .timeline-dot {
  right: -7px;
}

.timeline-right .timeline-dot {
  left: -7px;
}

.timeline-card {
  background: var(--main-bg-color);
  border-radius: var(--main-radius);
  padding: 16px 20px;
  box-shadow: 0 2px 8px var(--main-shadow);
  transition: box-shadow 0.2s, transform 0.2s;
}

.timeline-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.timeline-date {
  font-size: 12px;
  color: var(--theme-color);
  font-weight: 600;
  margin-bottom: 4px;
}

.timeline-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--main-color);
  margin-bottom: 4px;
}

.timeline-desc {
  font-size: 13px;
  color: var(--muted-color);
  line-height: 1.5;
}

/* Contact */
.contact-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--main-bg-color);
  border-radius: var(--main-radius);
  padding: 14px 20px;
  box-shadow: 0 2px 8px var(--main-shadow);
  font-size: 14px;
  color: var(--main-color);
}

.contact-item a {
  color: var(--theme-color);
  text-decoration: none;
}

.contact-item a:hover {
  text-decoration: underline;
}

.contact-icon {
  font-size: 18px;
}

/* Mobile: single-side timeline */
@media (max-width: 768px) {
  .about-site-name { font-size: 24px; }
  .about-stats { gap: 16px; }
  .about-stat-item { padding: 8px 16px; }
  .about-stat-num { font-size: 22px; }

  .timeline::before {
    left: 12px;
  }
  .timeline-item,
  .timeline-item.timeline-left,
  .timeline-item.timeline-right {
    width: 100%;
    margin-left: 0;
    padding-left: 36px;
    padding-right: 0;
    text-align: left;
  }
  .timeline-left .timeline-dot,
  .timeline-right .timeline-dot {
    left: 6px;
    right: auto;
  }
}
</style>
