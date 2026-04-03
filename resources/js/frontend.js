// Import Bootstrap JS
import 'bootstrap/js/dist/collapse';
import 'bootstrap/js/dist/dropdown';
import 'bootstrap/js/dist/modal';

// Submenu Toggle Function
window.toggleSubmenu = function(element) {
    const parentItem = element.closest('.aside-item');
    const submenu = parentItem.querySelector('.aside-sub');
    const arrow = element.querySelector('svg');

    if (submenu) {
        submenu.classList.toggle('show');
        submenu.classList.toggle('d-none');
        if (arrow) {
            arrow.classList.toggle('rotate-180');
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {

    // ===== Sidebar Init: show aside-body after page load =====
    const asideBody = document.querySelector('.aside-body');
    if (asideBody) {
        setTimeout(() => {
            asideBody.classList.add('show');
        }, 300);
    }

    // ===== Content Tab Switching =====
    document.querySelectorAll('.slider-tab').forEach(tabBar => {
        const tabItems = tabBar.querySelectorAll('.tab-item');
        const contentCard = tabBar.closest('.content-card');
        if (!contentCard) return;
        const panes = contentCard.querySelectorAll('.tab-pane');

        tabItems.forEach(item => {
            item.addEventListener('click', function () {
                // Update active tab
                tabItems.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                // Show corresponding pane
                const target = this.dataset.target;
                panes.forEach(p => {
                    const isActive = '#' + p.id === target;
                    p.classList.toggle('active', isActive);
                    p.style.display = isActive ? '' : 'none';
                });
            });
        });
    });

    // ===== Sidebar smooth scroll + tab auto-switch =====
    document.querySelectorAll('a.smooth').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (!href || !href.startsWith('#term-')) return;
            e.preventDefault();
            const target = document.querySelector(href);
            if (!target) return;

            // If target is a tab item (child category), click it to activate
            if (target.classList.contains('tab-item')) {
                target.click();
            }

            // Scroll to parent section
            const section = target.closest('.content-card');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ===== Scroll shadow for header nav =====
    window.addEventListener('scroll', function () {
        document.body.classList.toggle('scroll', window.scrollY > 10);
    });

    // ===== Search: Category Tabs (Top Level) =====
    const catTabs = document.querySelectorAll('.search-menu');
    const searchGroups = document.querySelectorAll('.search-group');
    const searchInput = document.getElementById('site-search');
    const searchBtn = document.querySelector('.search-submit-btn');
    let currentEngine = 'site';
    let currentEngineUrl = '';

    catTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            // Update active category tab
            catTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Show corresponding search group
            const target = this.dataset.target;
            searchGroups.forEach(g => {
                if (g.id === target) {
                    g.classList.add('active');
                } else {
                    g.classList.remove('active');
                }
            });

            // Activate first engine in new group
            const firstEngine = document.querySelector(`.search-group.active .search-term`);
            if (firstEngine) {
                searchGroups.forEach(g => g.querySelectorAll('.search-term').forEach(t => t.classList.remove('active')));
                firstEngine.classList.add('active');
                currentEngine = 'site';
                currentEngineUrl = '';
                if (searchInput) {
                    searchInput.placeholder = firstEngine.dataset.placeholder || '搜索...';
                    searchInput.value = '';
                    document.querySelectorAll('.posts-item, .sites-item').forEach(c => c.classList.remove('search-hidden'));
                    document.querySelectorAll('section').forEach(s => s.style.display = '');
                }
            }
        });
    });

    // ===== Search: Engine Tabs (Second Level) =====
    const engineTerms = document.querySelectorAll('.search-term');

    engineTerms.forEach(term => {
        term.addEventListener('click', function () {
            // Only deactivate siblings in same group
            const group = this.closest('.search-group');
            if (group) {
                group.querySelectorAll('.search-term').forEach(t => t.classList.remove('active'));
            }
            this.classList.add('active');
            currentEngine = this.dataset.id || 'site';
            currentEngineUrl = this.dataset.url || '';
            if (searchInput) {
                searchInput.placeholder = this.dataset.placeholder || '搜索...';
                searchInput.value = '';
                if (currentEngine !== 'site') {
                    document.querySelectorAll('.posts-item, .sites-item').forEach(c => c.classList.remove('search-hidden'));
                    document.querySelectorAll('section').forEach(s => s.style.display = '');
                }
            }
        });
    });

    // ===== Search Submit =====
    function doSearch() {
        const keyword = searchInput.value.trim();
        if (!keyword) return;

        if (currentEngine === 'site') {
            filterCards(keyword.toLowerCase());
        } else if (currentEngineUrl) {
            window.open(currentEngineUrl + encodeURIComponent(keyword), '_blank');
        }
    }

    if (searchBtn) searchBtn.addEventListener('click', doSearch);

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') doSearch();
        });
        searchInput.addEventListener('input', function () {
            if (currentEngine !== 'site') return;
            filterCards(this.value.toLowerCase().trim());
        });
    }

    function filterCards(keyword) {
        document.querySelectorAll('.posts-item, .sites-item').forEach(card => {
            const title = (card.dataset.title || '').toLowerCase();
            const desc = (card.dataset.desc || '').toLowerCase();
            const url = (card.dataset.url || '').toLowerCase();
            const match = !keyword || title.includes(keyword) || desc.includes(keyword) || url.includes(keyword);
            card.classList.toggle('search-hidden', !match);
        });
        document.querySelectorAll('section').forEach(section => {
            const visibleCards = section.querySelectorAll('.posts-item:not(.search-hidden), .sites-item:not(.search-hidden)');
            section.style.display = visibleCards.length === 0 ? 'none' : '';
        });
    }

    // ===== Click Tracking =====
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a.posts-item, a.sites-item');
        if (link && link.dataset.siteId) {
            const siteId = link.dataset.siteId;
            fetch('/api/click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ site_id: siteId }),
            }).catch(() => {});
        }
    });

    // ===== Sidebar Navigation =====
    const sidebarLinks = document.querySelectorAll('.sidebar-link');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.dataset.target;

            // Update active state
            sidebarLinks.forEach(l => {
                l.classList.remove('active');
            });
            this.classList.add('active');

            // Scroll to target section
            if (target === 'all') {
                document.querySelectorAll('section').forEach(s => s.style.display = '');
                document.querySelectorAll('.posts-item, .sites-item').forEach(c => c.classList.remove('search-hidden'));
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                const el = document.getElementById(target);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            // Close mobile sidebar
            if (window.innerWidth < 1024) {
                const aside = document.querySelector('.ioui-aside');
                if (aside) {
                    aside.classList.remove('is-mobile');
                }
            }
        });
    });

    // ===== Sidebar active on scroll =====
    const sections = document.querySelectorAll('section');
    const observerOptions = {
        root: null,
        rootMargin: '-80px 0px -60% 0px',
        threshold: 0
    };

    const sectionObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                sidebarLinks.forEach(l => {
                    l.classList.remove('active');
                });
                const activeLink = document.querySelector(`.sidebar-link[data-target="${id}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }
        });
    }, observerOptions);

    sections.forEach(section => sectionObserver.observe(section));

    // ===== Sidebar Collapse Toggle =====
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const sidebarCollapseText = document.getElementById('sidebarCollapseText');
    if (sidebarCollapseBtn && asideBody) {
        sidebarCollapseBtn.addEventListener('click', function () {
            asideBody.classList.toggle('sidebar-collapsed');
            if (asideBody.classList.contains('sidebar-collapsed')) {
                sidebarCollapseText.textContent = '展开';
            } else {
                sidebarCollapseText.textContent = '收起';
            }
        });
    }

    // ===== Quick Add: Auto Fetch URL =====
    const quickAddUrl = document.getElementById('quick-add-url');
    const quickAddTitle = document.getElementById('quick-add-title');
    const quickAddFavicon = document.getElementById('quick-add-favicon');
    const quickAddPreview = document.getElementById('quick-add-preview');
    let fetchTimer = null;

    if (quickAddUrl) {
        quickAddUrl.addEventListener('input', function () {
            clearTimeout(fetchTimer);
            const url = this.value.trim();
            if (!url || !url.startsWith('http')) return;

            fetchTimer = setTimeout(() => {
                quickAddUrl.disabled = true;
                fetch('/api/fetch-url', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ url: url }),
                })
                .then(res => res.json())
                .then(data => {
                    if (data.title) quickAddTitle.value = data.title;
                    if (data.favicon_url) {
                        quickAddFavicon.value = data.favicon_url;
                        quickAddPreview.src = data.favicon_url;
                        quickAddPreview.classList.remove('hidden');
                    }
                })
                .catch(() => {})
                .finally(() => { quickAddUrl.disabled = false; });
            }, 600);
        });
    }

    // ===== Quick Add: Submit =====
    const quickAddForm = document.getElementById('quick-add-form');
    if (quickAddForm) {
        quickAddForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = '添加中...';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            fetch('/api/quick-add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    quickAddForm.reset();
                    quickAddPreview.classList.add('hidden');
                    window.location.reload();
                } else {
                    alert(result.message || '添加失败，请重试');
                }
            })
            .catch(() => alert('网络错误，请重试'))
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '添加';
            });
        });
    }

});
