<button
    type="button"
    class="cursor-pointer rounded-full bg-ink px-2.5 py-1 text-xs font-semibold text-white shadow-sm ring-1 ring-ink/10 transition hover:-translate-y-0.5 hover:bg-slate-700 hover:shadow-md hover:ring-2 hover:ring-slate-400"
    @click.stop="$dispatch('open-user-assignments', {
        type: 'projects',
        url: @js(route('users.tree.projects', $user)),
        name: @js($user->displayName()),
    })"
>
    {{ $user->projects_count }} {{ $user->projects_count === 1 ? 'project' : 'projects' }}
</button>
<button
    type="button"
    class="cursor-pointer rounded-full bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm ring-1 ring-brand-500/20 transition hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-md hover:ring-2 hover:ring-brand-300"
    @click.stop="$dispatch('open-user-assignments', {
        type: 'tasks',
        url: @js(route('users.tree.tasks', $user)),
        name: @js($user->displayName()),
    })"
>
    {{ $user->tasks_count }} {{ $user->tasks_count === 1 ? 'task' : 'tasks' }}
</button>
