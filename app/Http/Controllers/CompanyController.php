<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $companies = Company::query()
            ->withCount(['plants', 'users', 'projects'])
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

        return view('companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('companies.form', [
            'nextSortOrder' => Company::nextSortOrder(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCompany($request);
        $validated['sort_order'] = $validated['sort_order'] ?? Company::nextSortOrder();
        $company = Company::create($validated);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $company,
            description: 'Created company "'.$company->name.'"',
            module: 'companies',
            properties: ['attributes' => $company->toArray()],
        );

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company created successfully.');
    }

    public function edit(Company $company): View
    {
        return view('companies.form', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $this->validateCompany($request, $company);
        $before = $this->activityLogger->snapshot($company);
        $company->update($validated);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $company,
            description: 'Updated company "'.$company->name.'"',
            module: 'companies',
            properties: $this->activityLogger->diff($before, $this->activityLogger->snapshot($company)),
        );

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->plants()->exists()) {
            return back()->with('error', 'Cannot delete a company that has plants. Remove plants first.');
        }

        if ($company->users()->exists() || $company->projects()->exists()) {
            return back()->with('error', 'Cannot delete a company that is assigned to users or projects.');
        }

        $name = $company->name;
        $snapshot = $company->toArray();
        $company->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted company "'.$name.'"',
            module: 'companies',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    private function validateCompany(Request $request, ?Company $company = null): array
    {
        $id = $company?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:companies,name,'.$id],
            'code' => ['nullable', 'string', 'max:50', 'unique:companies,code,'.$id],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? ($company?->sort_order ?? Company::nextSortOrder()));

        if (empty($validated['code'])) {
            $validated['code'] = null;
        }

        return $validated;
    }
}
