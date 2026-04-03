import { ref, watch } from 'vue';

const currentTheme = ref(localStorage.getItem('theme') || 'orange');

export function useTheme() {
    function setTheme(theme) {
        currentTheme.value = theme;
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
    }

    // Initialize on load
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-theme', currentTheme.value);
    }

    const themes = [
        { id: 'orange', label: '橙色', gradient: 'linear-gradient(135deg, #fc7c3c 0%, #e33636 100%)' },
        { id: 'blue', label: '蓝色', gradient: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' },
        { id: 'green', label: '绿色', gradient: 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)' },
        { id: 'purple', label: '紫色', gradient: 'linear-gradient(135deg, #a855f7 0%, #9333ea 100%)' },
        { id: 'pink', label: '粉色', gradient: 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)' },
        { id: 'dark', label: '深色', gradient: 'linear-gradient(135deg, #1f2937 0%, #111827 100%)' },
    ];

    return { currentTheme, setTheme, themes };
}
