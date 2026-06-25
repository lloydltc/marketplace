{{--
    Theme toggle — a single icon button that flips light ⇄ dark.
    System preference is the default (nothing stored); the first click sets an
    explicit choice and persists it. Pairs with the no-FOUC head script that sets
    `.dark` before paint. Shows a moon in light mode, a sun in dark mode.
--}}
<button type="button" x-data="themeToggle()" @click="toggle()" x-cloak
        :aria-label="dark ? 'Switch to light theme' : 'Switch to dark theme'"
        :title="dark ? 'Light theme' : 'Dark theme'"
        class="inline-flex items-center justify-center size-9 rounded-lg text-neutral-300 hover:text-white hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
    {{-- moon (light mode → click for dark) --}}
    <svg x-show="!dark" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
    </svg>
    {{-- sun (dark mode → click for light) --}}
    <svg x-show="dark" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <circle cx="12" cy="12" r="4" />
        <path stroke-linecap="round" d="M12 2v2M12 20v2M4 12H2M22 12h-2M5.6 5.6 4.2 4.2M19.8 19.8l-1.4-1.4M18.4 5.6l1.4-1.4M4.2 19.8l1.4-1.4" />
    </svg>
</button>

@once
    <script>
        function themeToggle() {
            return {
                dark: document.documentElement.classList.contains('dark'),
                toggle() {
                    this.dark = !this.dark;
                    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', this.dark);
                },
                init() {
                    // While on the system default (no stored choice), follow the OS live.
                    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                        if (! localStorage.getItem('theme')) {
                            this.dark = e.matches;
                            document.documentElement.classList.toggle('dark', e.matches);
                        }
                    });
                },
            };
        }
    </script>
@endonce
