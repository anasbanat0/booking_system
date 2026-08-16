

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

function normalizeWesternDigits(value) {
    const arabicIndicOffset = 0x0660;
    const easternArabicIndicOffset = 0x06f0;

    return String(value).replace(/[\u0660-\u0669\u06f0-\u06f9]/g, digit => {
        const code = digit.charCodeAt(0);
        return String(code >= easternArabicIndicOffset ? code - easternArabicIndicOffset : code - arabicIndicOffset);
    });
}

function enforceEnglishInputs(root = document) {
    root.querySelectorAll('input, select, textarea').forEach(input => {
        input.lang = 'en';
        input.setAttribute('lang', 'en-US');

        if (input.matches('input[type="date"], input[type="number"], input[type="tel"], input[inputmode="numeric"], input[inputmode="decimal"], .english-numeric-input')) {
            input.dir = 'ltr';
            input.setAttribute('dir', 'ltr');
            input.classList.add('english-numeric-input');
        }
    });

    root.querySelectorAll('input[type="date"], input[type="number"], input[type="tel"], input[inputmode="numeric"], input[inputmode="decimal"]').forEach(input => {
        if (input.dataset.englishDigitsReady) {
            return;
        }

        input.dataset.englishDigitsReady = 'true';
        input.addEventListener('input', () => {
            const normalized = normalizeWesternDigits(input.value);

            if (normalized !== input.value) {
                input.value = normalized;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    enforceEnglishInputs();

    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    enforceEnglishInputs(node);
                }
            });
        });
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });
});

Alpine.start();
