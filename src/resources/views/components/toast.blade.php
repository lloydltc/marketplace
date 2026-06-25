{{--
    Toast hub — place once near the end of the layout body. Trigger from anywhere:
        $dispatch('toast', { type: 'success', message: 'Published', timeout: 5000 })
    Types: success | info | warning | danger (danger uses role=alert/assertive).
--}}
<div x-data="toastHub()" @toast.window="push($event.detail)"
     class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-4 z-toast flex flex-col gap-2 pointer-events-none">
    <template x-for="t in items" :key="t.id">
        <div x-show="t.show" x-cloak
             x-transition:enter="transition ease-standard duration-200"
             x-transition:enter-start="opacity-0 translate-y-[-8px]" x-transition:enter-end="opacity-100 translate-y-0"
             :role="t.type === 'danger' ? 'alert' : 'status'"
             :aria-live="t.type === 'danger' ? 'assertive' : 'polite'"
             class="pointer-events-auto w-full sm:w-80 flex items-start gap-3 rounded-lg bg-surface border border-base shadow-e3 px-4 py-3">
            <span class="mt-0.5 size-2 rounded-full shrink-0"
                  :class="{ 'bg-[rgb(var(--success))]': t.type==='success', 'bg-[rgb(var(--info))]': t.type==='info', 'bg-[rgb(var(--warning))]': t.type==='warning', 'bg-[rgb(var(--danger))]': t.type==='danger' }"></span>
            <p class="flex-1 text-body-sm text-[rgb(var(--text))]" x-text="t.message"></p>
            <button type="button" @click="dismiss(t.id)" aria-label="Dismiss"
                    class="text-[rgb(var(--text-muted))] hover:text-[rgb(var(--text))] focus-visible:outline-none">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" /></svg>
            </button>
        </div>
    </template>
</div>

@once
    <script>
        function toastHub() {
            return {
                items: [],
                push(detail) {
                    const t = {
                        id: Date.now() + Math.random(),
                        type: detail?.type || 'info',
                        message: detail?.message || '',
                        show: true,
                    };
                    this.items.push(t);
                    const timeout = detail?.timeout ?? (t.type === 'danger' ? 6000 : 4500);
                    setTimeout(() => this.dismiss(t.id), timeout);
                },
                dismiss(id) {
                    const t = this.items.find((x) => x.id === id);
                    if (t) t.show = false;
                    setTimeout(() => { this.items = this.items.filter((x) => x.id !== id); }, 200);
                },
            };
        }
    </script>
@endonce
