import { defineStore } from 'pinia';
import axios from 'axios';

const SEARCH_GROUPS = {
    search: {
        label: '搜索',
        engines: [
            { id: 'site', label: '本站', placeholder: '站内搜索...', type: 'site' },
            { id: 'baidu', label: '百度', placeholder: '百度一下', type: 'external', url: 'https://www.baidu.com/s?wd=' },
            { id: 'bing', label: 'Bing', placeholder: '微软Bing搜索', type: 'external', url: 'https://cn.bing.com/search?q=' },
            { id: 'toutiao', label: '头条', placeholder: '头条搜索', type: 'external', url: 'https://so.toutiao.com/search?dvpf=pc&source=input&keyword=' },
            { id: 'sogou', label: '搜狗', placeholder: '搜狗搜索', type: 'external', url: 'https://www.sogou.com/web?query=' },
            { id: 'google', label: 'Google', placeholder: '谷歌两下', type: 'external', url: 'https://www.google.com/search?q=' },
        ],
    },
    community: {
        label: '社区',
        engines: [
            { id: 'zhihu', label: '知乎', placeholder: '知乎', type: 'external', url: 'https://www.zhihu.com/search?type=content&q=' },
            { id: 'wechat', label: '微信', placeholder: '微信', type: 'external', url: 'https://weixin.sogou.com/weixin?type=2&query=' },
            { id: 'weibo', label: '微博', placeholder: '微博', type: 'external', url: 'https://s.weibo.com/weibo/' },
            { id: 'douban', label: '豆瓣', placeholder: '豆瓣', type: 'external', url: 'https://www.douban.com/search?q=' },
        ],
    },
    life: {
        label: '生活',
        engines: [
            { id: 'taobao', label: '淘宝', placeholder: '淘宝', type: 'external', url: 'https://s.taobao.com/search?q=' },
            { id: 'jd', label: '京东', placeholder: '京东', type: 'external', url: 'https://search.jd.com/Search?keyword=' },
            { id: '12306', label: '12306', placeholder: '12306', type: 'external', url: 'https://www.12306.cn/?' },
        ],
    },
    video: {
        label: '视频',
        engines: [
            { id: 'bilibili', label: '哔哩哔哩', placeholder: '哔哩哔哩', type: 'external', url: 'https://search.bilibili.com/all?keyword=' },
            { id: 'douyin', label: '抖音', placeholder: '抖音', type: 'external', url: 'https://www.douyin.com/search/' },
            { id: 'qqv', label: '腾讯视频', placeholder: '腾讯视频', type: 'external', url: 'https://v.qq.com/x/search/?q=' },
        ],
    },
    music: {
        label: '音乐',
        engines: [
            { id: '163music', label: '网易云', placeholder: '搜索音乐、MV、歌单', type: 'external', url: 'https://music.163.com/#/search/m/?s=' },
            { id: 'qqmusic', label: 'QQ音乐', placeholder: '搜索音乐、MV、歌单', type: 'external', url: 'https://y.qq.com/n/ryqq/search?w=' },
        ],
    },
};

export const useSearchStore = defineStore('search', {
    state: () => ({
        keyword: '',
        activeGroup: 'search',
        activeEngine: 'site',
        searchResults: [],
        filtering: false,
    }),

    getters: {
        groups() {
            return Object.entries(SEARCH_GROUPS).map(([key, group]) => ({
                id: key,
                label: group.label,
            }));
        },
        currentEngines(state) {
            const group = SEARCH_GROUPS[state.activeGroup];
            return group ? group.engines : [];
        },
        currentEngine(state) {
            const engines = SEARCH_GROUPS[state.activeGroup]?.engines || [];
            return engines.find(e => e.id === state.activeEngine) || engines[0];
        },
        placeholder(state) {
            return SEARCH_GROUPS[state.activeGroup]?.engines.find(e => e.id === state.activeEngine)?.placeholder || '搜索...';
        },
    },

    actions: {
        setGroup(groupId) {
            this.activeGroup = groupId;
            const engines = SEARCH_GROUPS[groupId]?.engines || [];
            this.activeEngine = engines[0]?.id || 'site';
            this.keyword = '';
            this.searchResults = [];
        },

        setEngine(engineId) {
            this.activeEngine = engineId;
        },

        async doSearch() {
            const keyword = this.keyword.trim();
            if (!keyword) return;

            const engine = this.currentEngine;
            if (!engine) return;

            if (engine.type === 'site') {
                this.filtering = true;
                this.searchResults = [];
                try {
                    const { data } = await axios.get('/api/search', { params: { q: keyword } });
                    this.searchResults = data;
                } catch (e) {
                    this.searchResults = [];
                } finally {
                    this.filtering = false;
                }
            } else if (engine.url) {
                window.open(engine.url + encodeURIComponent(keyword), '_blank');
            }
        },
    },
});
