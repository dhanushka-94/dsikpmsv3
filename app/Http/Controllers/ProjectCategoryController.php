<?php

namespace App\Http\Controllers;

use App\Models\ProjectCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectCategoryController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $categories = ProjectCategory::query()
            ->withCount('projects')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return view('project-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('project-categories.create', [
            'nextSortOrder' => ProjectCategory::nextSortOrder(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);
        $category = ProjectCategory::create($validated);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $category,
            description: 'Created project category "'.$category->name.'"',
            module: 'project_categories',
            properties: ['attributes' => $category->toArray()],
        );

        return redirect()
            ->route('project-categories.index')
            ->with('success', 'Project category created successfully.');
    }

    public function edit(ProjectCategory $projectCategory): View
    {
        return view('project-categories.edit', [
            'category' => $projectCategory,
        ]);
    }

    public function update(Request $request, ProjectCategory $projectCategory): RedirectResponse
    {
        $validated = $this->validateCategory($request, $projectCategory);
        $before = $this->activityLogger->snapshot($projectCategory);
        $projectCategory->update($validated);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $projectCategory,
            description: 'Updated project category "'.$projectCategory->name.'"',
            module: 'project_categories',
            properties: $this->activityLogger->diff($before, $this->activityLogger->snapshot($projectCategory)),
        );

        return redirect()
            ->route('project-categories.index')
            ->with('success', 'Project category updated successfully.');
    }

    public function destroy(ProjectCategory $projectCategory): RedirectResponse
    {
        if ($projectCategory->projects()->exists()) {
            return back()->with('error', 'Cannot delete a category that has projects assigned.');
        }

        $name = $projectCategory->name;
        $snapshot = $projectCategory->toArray();
        $projectCategory->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted project category "'.$name.'"',
            module: 'project_categories',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('project-categories.index')
            ->with('success', 'Project category deleted successfully.');
    }

    private function validateCategory(Request $request, ?ProjectCategory $category = null): array
    {
        $id = $category?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:project_categories,name,'.$id],
            'code' => ['nullable', 'string', 'max:50', 'unique:project_categories,code,'.$id],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? ($category?->sort_order ?? ProjectCategory::nextSortOrder()));

        return $validated;
    }
}
