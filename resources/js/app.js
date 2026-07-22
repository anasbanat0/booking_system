

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.themePreference = () => ({
    isDark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.isDark = ! this.isDark;
        document.documentElement.classList.toggle('dark', this.isDark);
        document.documentElement.style.colorScheme = this.isDark ? 'dark' : 'light';
        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: this.isDark } }));
    },
});

Alpine.start();
