<div
    x-data="{
        open: false,
        src: null,
        alt: '',
        show(detail) {
            this.src = detail.src;
            this.alt = detail.alt || 'Profile photo';
            this.open = true;
            document.body.classList.add('overflow-hidden');
        },
        hide() {
            this.open = false;
            this.src = null;
            document.body.classList.remove('overflow-hidden');
        }
    }"
    @open-lightbox.window="show($event.detail)"
    @keydown.escape.window="open && hide()"
>
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-8"
        role="dialog"
        aria-modal="true"
        aria-label="Photo lightbox"
    >
        <div
            class="absolute inset-0 bg-ink/70 backdrop-blur-sm"
            x-show="open"
            x-transition.opacity
            @click="hide()"
        ></div>

        <div
            class="relative z-10 flex max-h-full w-full max-w-4xl flex-col items-center"
            x-show="open"
            x-transition
            @click.stop
        >
            <button
                type="button"
                class="mb-3 self-end rounded-full bg-white/95 px-3 py-1.5 text-sm font-bold text-slate-700 shadow hover:bg-white"
                @click="hide()"
            >
                Close
            </button>
            <div class="flex max-h-[80vh] w-full items-center justify-center overflow-hidden rounded-3xl bg-white p-2 shadow-2xl sm:p-4">
                <img
                    :src="src"
                    :alt="alt"
                    class="max-h-[76vh] max-w-full object-contain"
                >
            </div>
        </div>
    </div>
</div>
