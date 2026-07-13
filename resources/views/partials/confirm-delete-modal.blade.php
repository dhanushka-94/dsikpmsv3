<div
    x-data="confirmDeleteDialog()"
    x-cloak
    @confirm-delete.window="ask($event.detail)"
    @keydown.escape.window="open && close()"
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-ink/50 p-4"
        @click.self="close()"
    >
        <div
            x-show="open"
            x-transition
            class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-xl"
            role="dialog"
            aria-modal="true"
            @click.stop
        >
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-extrabold tracking-tight" x-text="title"></h3>
                    <p class="mt-1 text-sm text-muted" x-text="message"></p>
                </div>
            </div>

            <template x-if="requireMath">
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">
                        Solve this to confirm:
                        <span class="font-extrabold" x-text="mathA + ' + ' + mathB + ' = ?'"></span>
                    </p>
                    <input
                        type="number"
                        x-model="answer"
                        x-ref="mathInput"
                        inputmode="numeric"
                        placeholder="Your answer"
                        class="mt-3 w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                        @keydown.enter.prevent="confirm()"
                    >
                    <p x-show="error" class="mt-2 text-xs font-semibold text-red-600" x-text="error"></p>
                </div>
            </template>

            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600" @click="close()">
                    Cancel
                </button>
                <button type="button" class="rounded-2xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-700" @click="confirm()">
                    <span x-text="requireMath ? 'Verify & delete' : 'Delete'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
    function confirmDeleteDialog() {
        return {
            open: false,
            title: 'Confirm delete',
            message: 'This action cannot be undone.',
            requireMath: false,
            mathA: 0,
            mathB: 0,
            answer: '',
            error: '',
            form: null,
            ask(detail) {
                this.form = detail.form || null;
                this.title = detail.title || 'Confirm delete';
                this.message = detail.message || 'This action cannot be undone.';
                this.requireMath = !!detail.requireMath;
                this.answer = '';
                this.error = '';
                if (this.requireMath) {
                    this.mathA = Math.floor(Math.random() * 9) + 1;
                    this.mathB = Math.floor(Math.random() * 9) + 1;
                }
                this.open = true;
                this.$nextTick(() => {
                    if (this.requireMath && this.$refs.mathInput) {
                        this.$refs.mathInput.focus();
                    }
                });
            },
            close() {
                this.open = false;
                this.form = null;
                this.error = '';
                this.answer = '';
            },
            confirm() {
                if (!this.form) return;

                if (this.requireMath) {
                    const expected = this.mathA + this.mathB;
                    if (String(this.answer).trim() === '' || Number(this.answer) !== expected) {
                        this.error = 'Incorrect answer. Please try again.';
                        this.mathA = Math.floor(Math.random() * 9) + 1;
                        this.mathB = Math.floor(Math.random() * 9) + 1;
                        this.answer = '';
                        this.$nextTick(() => this.$refs.mathInput?.focus());
                        return;
                    }

                    this.ensureHidden('confirm_a', this.mathA);
                    this.ensureHidden('confirm_b', this.mathB);
                    this.ensureHidden('confirm_answer', this.answer);
                }

                const form = this.form;
                this.close();
                form.submit();
            },
            ensureHidden(name, value) {
                let input = this.form.querySelector(`input[name="${name}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    this.form.appendChild(input);
                }
                input.value = value;
            },
        };
    }

    function requestDeleteConfirm(event, options = {}) {
        event.preventDefault();
        const form = event.target.closest('form') || event.target;
        window.dispatchEvent(new CustomEvent('confirm-delete', {
            detail: {
                form,
                title: options.title || 'Confirm delete',
                message: options.message || 'This action cannot be undone.',
                requireMath: !!options.requireMath,
            },
        }));
        return false;
    }
</script>
