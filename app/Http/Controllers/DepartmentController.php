<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $departments = Department::query()
            ->with(['parent'])
            ->withCount(['users', 'children'])
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

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('departments.create', [
            'nextSortOrder' => Department::nextSortOrder(),
            'parentDepartments' => Department::optionsForSelect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDepartment($request);
        $validated['sort_order'] = $validated['sort_order'] ?? Department::nextSortOrder();
        $department = Department::create($validated);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $department,
            description: 'Created department "'.$department->name.'"',
            module: 'departments',
            properties: ['attributes' => $department->toArray()],
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', [
            'department' => $department,
            'parentDepartments' => Department::optionsForSelect($department),
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $this->validateDepartment($request, $department);
        $before = $this->activityLogger->snapshot($department);
        $department->update($validated);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $department,
            description: 'Updated department "'.$department->name.'"',
            module: 'departments',
            properties: $this->activityLogger->diff($before, $this->activityLogger->snapshot($department)),
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Cannot delete a department that has users assigned.');
        }

        if ($department->children()->exists()) {
            return back()->with('error', 'Cannot delete a department that has child departments. Reassign or remove them first.');
        }

        $name = $department->name;
        $snapshot = $department->toArray();
        $department->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted department "'.$name.'"',
            module: 'departments',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    private function validateDepartment(Request $request, ?Department $department = null): array
    {
        $id = $department?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,'.$id],
            'code' => ['nullable', 'string', 'max:50', 'unique:departments,code,'.$id],
            'parent_id' => ['nullable', 'exists:departments,id', Rule::notIn(array_filter([(string) $id]))],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? ($department?->sort_order ?? Department::nextSortOrder()));
        $validated['parent_id'] = ! empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($department && $department->wouldCreateCycle($validated['parent_id'])) {
            throw ValidationException::withMessages([
                'parent_id' => 'A department cannot be nested under itself or one of its child departments.',
            ]);
        }

        return $validated;
    }
}
