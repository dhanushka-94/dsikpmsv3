<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plant;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlantController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $plants = Plant::query()
            ->with('company')
            ->withCount(['users', 'projects'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return view('plants.index', [
            'plants' => $plants,
            'companies' => Company::ordered()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = $request->integer('company_id') ?: null;

        return view('plants.form', [
            'companies' => Company::where('is_active', true)->ordered()->get(),
            'selectedCompanyId' => $companyId,
            'nextSortOrder' => Plant::nextSortOrder($companyId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlant($request);
        $validated['sort_order'] = $validated['sort_order'] ?? Plant::nextSortOrder($validated['company_id']);
        $plant = Plant::create($validated);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $plant,
            description: 'Created plant "'.$plant->name.'"',
            module: 'plants',
            properties: ['attributes' => $plant->toArray()],
        );

        return redirect()
            ->route('plants.index', ['company_id' => $plant->company_id])
            ->with('success', 'Plant created successfully.');
    }

    public function edit(Plant $plant): View
    {
        return view('plants.form', [
            'plant' => $plant,
            'companies' => Company::where('is_active', true)->ordered()->get(),
            'selectedCompanyId' => $plant->company_id,
        ]);
    }

    public function update(Request $request, Plant $plant): RedirectResponse
    {
        $validated = $this->validatePlant($request, $plant);
        $before = $this->activityLogger->snapshot($plant);
        $plant->update($validated);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $plant,
            description: 'Updated plant "'.$plant->name.'"',
            module: 'plants',
            properties: $this->activityLogger->diff($before, $this->activityLogger->snapshot($plant)),
        );

        return redirect()
            ->route('plants.index', ['company_id' => $plant->company_id])
            ->with('success', 'Plant updated successfully.');
    }

    public function destroy(Plant $plant): RedirectResponse
    {
        if ($plant->users()->exists() || $plant->projects()->exists()) {
            return back()->with('error', 'Cannot delete a plant that is assigned to users or projects.');
        }

        $name = $plant->name;
        $companyId = $plant->company_id;
        $snapshot = $plant->toArray();
        $plant->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted plant "'.$name.'"',
            module: 'plants',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('plants.index', ['company_id' => $companyId])
            ->with('success', 'Plant deleted successfully.');
    }

    private function validatePlant(Request $request, ?Plant $plant = null): array
    {
        $id = $plant?->id;

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plants', 'name')
                    ->where(fn ($q) => $q->where('company_id', $request->input('company_id')))
                    ->ignore($id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('plants', 'code')
                    ->where(fn ($q) => $q->where('company_id', $request->input('company_id')))
                    ->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? ($plant?->sort_order ?? Plant::nextSortOrder((int) $validated['company_id'])));

        if (empty($validated['code'])) {
            $validated['code'] = null;
        }

        return $validated;
    }
}
