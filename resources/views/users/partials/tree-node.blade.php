@php
    /** @var array{user: \App\Models\User, children: \Illuminate\Support\Collection} $node */
    $user = $node['user'];
    $children = $node['children'];
    $hasChildren = $children->isNotEmpty();
@endphp

<div class="relative {{ $depth > 0 ? 'ml-5 border-l-2 border-brand-100 pl-5 sm:ml-8 sm:pl-8' : '' }}">
    @if($depth > 0)
        <span class="absolute -left-[2px] top-8 h-0.5 w-5 bg-brand-100 sm:w-8"></span>
    @endif

    <div class="group relative mb-4 flex gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3 transition hover:border-brand-200 hover:bg-white hover:shadow-md sm:p-4">
        <div class="relative shrink-0">
            @if($user->profilePictureUrl())
                <img src="{{ $user->profilePictureUrl() }}" alt="" class="h-12 w-12 rounded-2xl object-cover ring-2 ring-white shadow-sm sm:h-14 sm:w-14">
            @else
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-100 text-base font-extrabold text-brand-700 ring-2 ring-white shadow-sm sm:h-14 sm:w-14 sm:text-lg">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <span class="absolute -bottom-1 -right-1 h-3.5 w-3.5 rounded-full border-2 border-white {{ $user->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="truncate text-base font-bold text-ink">{{ $user->displayName() }}</p>
                </div>
                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                    {{ $user->role->label() }}
                </span>
            </div>

            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                @if($user->designation)
                    <span class="rounded-full bg-brand-50 px-2.5 py-1 font-semibold text-brand-700 ring-1 ring-brand-100">
                        #{{ $user->designation->sort_order }} {{ $user->designation->name }}
                    </span>
                @endif
                @if($user->department)
                    <span class="rounded-full bg-white px-2.5 py-1 font-semibold text-slate-600 ring-1 ring-slate-200">
                        {{ $user->department->displayName() }}
                    </span>
                @endif
                <span class="rounded-full bg-ink px-2.5 py-1 font-semibold text-white">
                    {{ $user->projects_count }} {{ $user->projects_count === 1 ? 'project' : 'projects' }}
                </span>
            </div>
        </div>
    </div>

    @if($hasChildren)
        <div>
            @foreach($children as $childIndex => $child)
                @include('users.partials.tree-node', [
                    'node' => $child,
                    'isLast' => $childIndex === $children->count() - 1,
                    'depth' => $depth + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
