<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $designations = Designation::query()
            ->withCount('users')
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

        return view('designations.index', compact('designations'));
    }

    public function create(): View
    {
        return view('designations.create', [
            'nextSortOrder' => Designation::nextSortOrder(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDesignation($request);
        $validated['sort_order'] = $validated['sort_order'] ?? Designation::nextSortOrder();
        $designation = Designation::create($validated);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $designation,
            description: 'Created designation "'.$designation->name.'"',
            module: 'designations',
            properties: ['attributes' => $designation->toArray()],
        );

        return redirect()
            ->route('designations.index')
            ->with('success', 'Designation created successfully.');
    }

    public function edit(Designation $designation): View
    {
        return view('designations.edit', compact('designation'));
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $validated = $this->validateDesignation($request, $designation);
        $before = $this->activityLogger->snapshot($designation);
        $designation->update($validated);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $designation,
            description: 'Updated designation "'.$designation->name.'"',
            module: 'designations',
            properties: $this->activityLogger->diff($before, $this->activityLogger->snapshot($designation)),
        );

        return redirect()
            ->route('designations.index')
            ->with('success', 'Designation updated successfully.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        if ($designation->users()->exists()) {
            return back()->with('error', 'Cannot delete a designation that has users assigned.');
        }

        $name = $designation->name;
        $snapshot = $designation->toArray();
        $designation->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted designation "'.$name.'"',
            module: 'designations',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('designations.index')
            ->with('success', 'Designation deleted successfully.');
    }

    private function validateDesignation(Request $request, ?Designation $designation = null): array
    {
        $id = $designation?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:designations,name,'.$id],
            'code' => ['nullable', 'string', 'max:50', 'unique:designations,code,'.$id],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? ($designation?->sort_order ?? Designation::nextSortOrder()));

        return $validated;
    }
}
