<div
    x-show="visible"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-ink/50 p-4"
    @click.self="close()"
>
    <div class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl" @click.stop>
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-muted" x-text="typeLabel"></p>
                <h3 class="text-lg font-extrabold tracking-tight" x-text="userName"></h3>
            </div>
            <button type="button" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600" @click="close()">Close</button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4">
            <template x-if="loading">
                <p class="py-10 text-center text-sm text-muted">Loading...</p>
            </template>

            <template x-if="!loading && error">
                <p class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" x-text="error"></p>
            </template>

            <template x-if="!loading && !error && items.length === 0">
                <p class="py-10 text-center text-sm text-muted" x-text="'No ' + typeLabel.toLowerCase() + ' found.'"></p>
            </template>

            <div class="space-y-3" x-show="!loading && !error && items.length">
                <template x-for="item in items" :key="item.id">
                    <a
                        :href="item.url || '#'"
                        class="block rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 transition hover:border-brand-200 hover:bg-white hover:shadow-sm"
                        :class="!item.url && 'pointer-events-none'"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="font-bold text-ink" x-text="item.title"></p>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold" :class="item.badge_class" x-text="item.badge"></span>
                        </div>
                        <p class="mt-1 text-xs text-muted" x-show="item.meta" x-text="item.meta"></p>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-slate-500">
                            <span x-show="item.dates" x-text="item.dates"></span>
                            <span x-show="item.extra" x-text="item.extra"></span>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function userAssignmentsModal() {
                return {
                    visible: false,
                    loading: false,
                    error: '',
                    userName: '',
                    type: 'projects',
                    items: [],
                    get typeLabel() {
                        return this.type === 'tasks' ? 'Tasks' : 'Projects';
                    },
                    async open(detail) {
                        this.visible = true;
                        this.loading = true;
                        this.error = '';
                        this.items = [];
                        this.userName = detail.name || '';
                        this.type = detail.type || 'projects';

                        try {
                            const response = await fetch(detail.url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (!response.ok) {
                                throw new Error('Unable to load details.');
                            }

                            const data = await response.json();
                            this.userName = data.user || this.userName;
                            this.type = data.type || this.type;
                            this.items = data.items || [];
                        } catch (e) {
                            this.error = e.message || 'Unable to load details.';
                        } finally {
                            this.loading = false;
                        }
                    },
                    close() {
                        this.visible = false;
                        this.items = [];
                        this.error = '';
                    },
                };
            }
        </script>
    @endpush
@endonce
