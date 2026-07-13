<?php

namespace App\Http\Controllers;

use App\Models\KpiCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KpiCategoryController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $categories = KpiCategory::query()
            ->withCount('kpis')
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

        return view('kpi-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('kpi-categories.form', [
            'nextSortOrder' => KpiCategory::nextSortOrder(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);
        $category = KpiCategory::create($validated);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $category,
            description: 'Created KPI category "'.$category->name.'"',
            module: 'kpi_categories',
            properties: ['attributes' => $this->activityLogger->snapshot($category)],
        );

        return redirect()
            ->route('kpi-categories.index')
            ->with('success', 'KPI category created successfully.');
    }

    public function edit(KpiCategory $kpiCategory): View
    {
        return view('kpi-categories.form', [
            'category' => $kpiCategory,
        ]);
    }

    public function update(Request $request, KpiCategory $kpiCategory): RedirectResponse
    {
        $validated = $this->validateCategory($request, $kpiCategory);
        $before = $this->activityLogger->snapshot($kpiCategory);
        $kpiCategory->update($validated);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $kpiCategory,
            description: 'Updated KPI category "'.$kpiCategory->name.'"',
            module: 'kpi_categories',
            properties: $this->activityLogger->diff($before, $this->activityLogger->snapshot($kpiCategory)),
        );

        return redirect()
            ->route('kpi-categories.index')
            ->with('success', 'KPI category updated successfully.');
    }

    public function destroy(KpiCategory $kpiCategory): RedirectResponse
    {
        if ($kpiCategory->kpis()->exists()) {
            $this->activityLogger->forModel(
                action: 'delete_blocked',
                subject: $kpiCategory,
                description: 'Blocked delete of KPI category "'.$kpiCategory->name.'" because KPIs are assigned',
                module: 'kpi_categories',
                properties: [
                    'kpis_count' => $kpiCategory->kpis()->count(),
                ],
            );

            return back()->with('error', 'Cannot delete a category that has KPIs assigned.');
        }

        $name = $kpiCategory->name;
        $snapshot = $this->activityLogger->snapshot($kpiCategory);
        $kpiCategory->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted KPI category "'.$name.'"',
            module: 'kpi_categories',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('kpi-categories.index')
            ->with('success', 'KPI category deleted successfully.');
    }

    private function validateCategory(Request $request, ?KpiCategory $category = null): array
    {
        $id = $category?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:kpi_categories,name,'.$id],
            'code' => ['nullable', 'string', 'max:50', 'unique:kpi_categories,code,'.$id],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? ($category?->sort_order ?? KpiCategory::nextSortOrder()));

        if (empty($validated['code'])) {
            $validated['code'] = null;
        }

        return $validated;
    }
}
