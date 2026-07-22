<button
    type="button"
    x-data="themePreference()"
    x-on:theme-changed.window="isDark = $event.detail.isDark"
    x-on:click="toggle()"
    class="group inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
    :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
>
    <svg x-show="! isDark" x-cloak class="h-5 w-5 text-amber-500 transition group-hover:rotate-12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM12 2a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1Zm0 17a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1ZM4.22 4.22a1 1 0 0 1 1.42 0l.7.7A1 1 0 0 1 4.93 6.34l-.71-.7a1 1 0 0 1 0-1.42Zm13.44 13.44a1 1 0 0 1 1.41 0l.71.7a1 1 0 0 1-1.42 1.42l-.7-.71a1 1 0 0 1 0-1.41ZM2 12a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1Zm17 0a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1ZM4.22 19.78a1 1 0 0 1 0-1.42l.7-.7a1 1 0 1 1 1.42 1.41l-.7.71a1 1 0 0 1-1.42 0ZM17.66 6.34a1 1 0 0 1 0-1.41l.7-.71a1 1 0 1 1 1.42 1.42l-.71.7a1 1 0 0 1-1.41 0Z" />
    </svg>
    <svg x-show="isDark" x-cloak class="h-5 w-5 text-sky-300 transition group-hover:-rotate-12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M21 14.4A8.8 8.8 0 0 1 9.6 3a.75.75 0 0 0-.86-1.08A10.5 10.5 0 1 0 22.08 15.26a.75.75 0 0 0-1.08-.86Z" />
    </svg>
</button>
