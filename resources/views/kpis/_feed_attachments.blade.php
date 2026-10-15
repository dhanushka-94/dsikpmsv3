<div x-data="{ attachmentNames: [] }">
    <label class="mb-1.5 block text-sm font-semibold">Attachment</label>
    <p class="mb-2 text-xs text-muted">Optional. Attach one Excel file, image, or PDF. Maximum 5 MB.</p>
    <input
        type="file"
        name="attachments[]"
        accept=".pdf,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        class="w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700"
        @change="attachmentNames = Array.from($event.target.files).map(file => file.name)"
    >
    <ul class="mt-2 space-y-1" x-show="attachmentNames.length" x-cloak>
        <template x-for="name in attachmentNames" :key="name">
            <li class="truncate text-xs font-semibold text-slate-600" x-text="name"></li>
        </template>
    </ul>
    @if(($showAttachmentErrors ?? true) === true)
        @error('attachments')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
        @foreach ($errors->get('attachments.*') as $messages)
            @foreach ($messages as $message)
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @endforeach
        @endforeach
    @endif
</div>
