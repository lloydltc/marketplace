import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

/**
 * Live search autocomplete. Fetches JSON suggestions from `endpoint` and,
 * on selection, fills the input and submits the surrounding form.
 */
Alpine.data('searchAutocomplete', (endpoint, initial = '') => ({
    query: initial ?? '',
    suggestions: [],
    open: false,

    async fetchSuggestions() {
        if (this.query.trim().length < 2) {
            this.suggestions = [];
            this.open = false;
            return;
        }

        try {
            const res = await fetch(`${endpoint}?q=${encodeURIComponent(this.query)}`, {
                headers: { Accept: 'application/json' },
            });
            this.suggestions = res.ok ? await res.json() : [];
            this.open = this.suggestions.length > 0;
        } catch (e) {
            this.suggestions = [];
            this.open = false;
        }
    },

    select(value) {
        this.query = value;
        this.open = false;
        this.$root.closest('form')?.submit();
    },
}));

window.Alpine = Alpine;
Alpine.start();
