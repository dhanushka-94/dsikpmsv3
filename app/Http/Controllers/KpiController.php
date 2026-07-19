<?php

namespace App\Http\Controllers;

use App\Enums\BenchmarkType;
use App\Enums\UserRole;
use App\Models\Kpi;
use App\Models\KpiCategory;
use App\Models\KpiResult;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FormulaEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class KpiController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $kpis = Kpi::query()
            ->with(['category'])
            ->withCount(['projects', 'users', 'results'])
            ->when(! $user->canManageUsers(), function ($query) use ($user) {
                $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('kpi_index', 'like', "%{$search}%")
                        ->orWhere('definition', 'like', "%{$search}%")
                        ->orWhere('formula', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('kpi_category_id'), fn ($q) => $q->where('kpi_category_id', $request->integer('kpi_category_id')))
            ->when($request->filled('benchmark_type'), fn ($q) => $q->where('benchmark_type', $request->string('benchmark_type')))
            ->orderBy('kpi_index')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('kpis.index', [
            'kpis' => $kpis,
            'categories' => KpiCategory::ordered()->get(),
            'benchmarkTypes' => BenchmarkType::options(),
            'canManage' => $user->canManageUsers(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeManage();

        return view('kpis.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $this->validateKpi($request);
        $projectIds = $this->validatedProjectIds($request);
        $assignments = $this->validatedAssignments($request);

        $kpi = Kpi::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $kpi->projects()->sync($projectIds);
        $kpi->users()->sync($assignments);
        $kpi->load(['category', 'projects', 'users']);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $kpi,
            description: 'Created KPI "'.$kpi->name.'"',
            module: 'kpis',
            properties: [
                'attributes' => $this->activityLogger->snapshot($kpi),
                'category' => $kpi->category?->name,
                'formula' => $kpi->formula,
                'formula_fields' => $kpi->formulaFieldDefinitions(),
                'projects' => $kpi->projects->map(fn ($project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                ])->values()->all(),
                'assignments' => $this->activityLogger->pivotAssignments($kpi->users, ['weightage']),
            ],
        );

        return redirect()
            ->route('kpis.show', $kpi)
            ->with('success', 'KPI created successfully.');
    }

    public function show(Request $request, Kpi $kpi): View
    {
        $this->authorizeView($kpi);

        $kpi->load([
            'category',
            'creator',
            'projects.company',
            'projects.plant',
            'users.designation',
        ]);

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');

        $allResults = $kpi->results()
            ->with('creator')
            ->when($dateFrom, fn ($query) => $query->whereDate('recorded_on', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('recorded_on', '<=', $dateTo))
            ->orderBy('id')
            ->get();

        // Chronological by recorded date (for chart). Progress "latest" uses last saved entry.
        $history = $allResults->sortBy([
            ['recorded_on', 'asc'],
            ['id', 'asc'],
        ])->values();

        $savedOrder = $allResults->values();
        $results = $savedOrder->map(fn ($entry) => (float) $entry->result);
        $latestEntry = $savedOrder->last();
        $latest = $latestEntry !== null ? (float) $latestEntry->result : null;
        $benchmark = (float) $kpi->benchmark_percent;
        $isIncrease = $kpi->benchmark_type === BenchmarkType::Increase;

        $vsBenchmark = null;
        $progressPercent = null;
        if ($latest !== null && $benchmark > 0) {
            $vsBenchmark = round($latest - $benchmark, 4);
            if ($isIncrease) {
                $progressPercent = round(min(100, max(0, ($latest / $benchmark) * 100)), 2);
            } else {
                $progressPercent = round(min(100, max(0, (2 - ($latest / $benchmark)) * 100)), 2);
            }
        }

        $chartData = $this->buildMonthlyProgressChart($kpi, $history, $dateFrom, $dateTo);

        $progressStats = [
            'entries' => $history->count(),
            'latest' => $latest,
            'latest_date' => $latestEntry?->recorded_on?->format('Y-m-d'),
            'latest_saved_at' => $latestEntry?->created_at,
            'average' => $results->isNotEmpty() ? round($results->avg(), 4) : null,
            'min' => $results->isNotEmpty() ? round($results->min(), 4) : null,
            'max' => $results->isNotEmpty() ? round($results->max(), 4) : null,
            'benchmark' => $benchmark,
            'vs_benchmark' => $vsBenchmark,
            'progress_percent' => $progressPercent,
            'trend' => $this->resultTrend($results->all()),
            'months' => count($chartData['labels']),
            'current_expected' => collect($chartData['expected'])->filter(fn ($v) => $v !== null)->last(),
        ];

        $userProgress = $kpi->users->map(function ($member) use ($progressPercent, $latest) {
            $weightage = (float) $member->pivot->weightage;
            $contribution = $progressPercent !== null
                ? round(($progressPercent * $weightage) / 100, 2)
                : null;

            return [
                'user' => $member,
                'weightage' => $weightage,
                'progress_percent' => $progressPercent,
                'contribution' => $contribution,
                'weighted_result' => $latest !== null
                    ? round($latest * ($weightage / 100), 4)
                    : null,
            ];
        })->values();

        $historyResults = $kpi->results()
            ->with('creator')
            ->when($dateFrom, fn ($query) => $query->whereDate('recorded_on', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('recorded_on', '<=', $dateTo))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $this->activityLogger->forModel(
            action: 'viewed',
            subject: $kpi,
            description: 'Viewed KPI "'.$kpi->name.'"',
            module: 'kpis',
            properties: [
                'category' => $kpi->category?->name,
                'formula' => $kpi->formula,
                'results_count' => $history->count(),
                'latest_result' => $latest,
                'progress_percent' => $progressPercent,
                'chart_range' => $chartData['rangeLabel'] ?? null,
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
            ],
        );

        return view('kpis.show', [
            'kpi' => $kpi,
            'chartData' => $chartData,
            'progressStats' => $progressStats,
            'historyResults' => $historyResults,
            'userProgress' => $userProgress,
            'canManage' => auth()->user()->canManageUsers(),
            'dateFrom' => $dateFrom?->format('Y-m-d'),
            'dateTo' => $dateTo?->format('Y-m-d'),
            'hasDateFilter' => $dateFrom !== null || $dateTo !== null,
            'financialYears' => $this->financialYearOptions($kpi, $dateFrom, $dateTo),
        ]);
    }

    /**
     * Financial years for Sri Lanka style period: 1 Apr → 31 Mar.
     *
     * @return list<array{label: string, from: string, to: string, active: bool}>
     */
    private function financialYearOptions(
        Kpi $kpi,
        ?\Carbon\CarbonInterface $dateFrom = null,
        ?\Carbon\CarbonInterface $dateTo = null,
    ): array {
        $periodStart = $kpi->start_date->copy()->startOfDay();
        $periodEnd = $kpi->end_date->copy()->startOfDay();

        if ($periodEnd->lt($periodStart)) {
            return [];
        }

        $fyStartYear = $periodStart->month >= 4 ? $periodStart->year : $periodStart->year - 1;
        $fyEndYear = $periodEnd->month >= 4 ? $periodEnd->year : $periodEnd->year - 1;

        $options = [];

        for ($year = $fyStartYear; $year <= $fyEndYear; $year++) {
            $fyFrom = \Illuminate\Support\Carbon::create($year, 4, 1)->startOfDay();
            $fyTo = \Illuminate\Support\Carbon::create($year + 1, 3, 31)->startOfDay();

            // Keep quick picks inside the KPI period.
            $from = $fyFrom->copy()->max($periodStart);
            $to = $fyTo->copy()->min($periodEnd);

            if ($to->lt($from)) {
                continue;
            }

            $fromStr = $from->format('Y-m-d');
            $toStr = $to->format('Y-m-d');
            $label = 'FY '.$year.'/'.substr((string) ($year + 1), -2);

            $active = $dateFrom?->format('Y-m-d') === $fromStr
                && $dateTo?->format('Y-m-d') === $toStr;

            $options[] = [
                'label' => $label,
                'from' => $fromStr,
                'to' => $toStr,
                'active' => $active,
            ];
        }

        return $options;
    }

    /**
     * Build monthly chart series for the KPI period, optionally narrowed by a date filter.
     *
     * @param  Collection<int, KpiResult>  $history
     * @return array{
     *     labels: list<string>,
     *     monthly: list<float|null>,
     *     expected: list<float>,
     *     benchmark: list<float>,
     *     benchmarkValue: float,
     *     hasActuals: bool,
     *     rangeLabel: string
     * }
     */
    private function buildMonthlyProgressChart(
        Kpi $kpi,
        Collection $history,
        ?\Carbon\CarbonInterface $dateFrom = null,
        ?\Carbon\CarbonInterface $dateTo = null,
    ): array {
        $start = $kpi->start_date->copy()->startOfMonth();
        $end = $kpi->end_date->copy()->startOfMonth();

        if ($dateFrom) {
            $start = $dateFrom->copy()->startOfMonth()->max($kpi->start_date->copy()->startOfMonth());
        }

        if ($dateTo) {
            $end = $dateTo->copy()->startOfMonth()->min($kpi->end_date->copy()->startOfMonth());
        }

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $months = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        $byMonth = $history->groupBy(fn ($entry) => $entry->recorded_on->format('Y-m'));
        $benchmark = (float) $kpi->benchmark_percent;
        $isIncrease = $kpi->benchmark_type === BenchmarkType::Increase;
        $totalMonths = max(count($months), 1);

        $firstMonthKey = $months[0]->format('Y-m');
        $firstMonthAvg = optional($byMonth->get($firstMonthKey))->avg(fn ($entry) => (float) $entry->result);
        $decreaseStart = max(
            $benchmark,
            (float) ($firstMonthAvg ?? $benchmark),
            $benchmark > 0 ? round($benchmark * 1.5, 4) : 100.0
        );

        $labels = [];
        $monthly = [];
        $expected = [];
        $benchmarkLine = [];

        foreach ($months as $index => $month) {
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');

            $group = $byMonth->get($key);
            $monthly[] = $group
                ? round((float) $group->avg(fn ($entry) => (float) $entry->result), 4)
                : null;

            $ratio = $totalMonths <= 1 ? 1.0 : ($index + 1) / $totalMonths;

            if ($isIncrease) {
                $expected[] = round($benchmark * $ratio, 4);
            } else {
                $expected[] = round($decreaseStart - (($decreaseStart - $benchmark) * $ratio), 4);
            }

            $benchmarkLine[] = $benchmark;
        }

        return [
            'labels' => $labels,
            'monthly' => $monthly,
            'expected' => $expected,
            'benchmark' => $benchmarkLine,
            'benchmarkValue' => $benchmark,
            'hasActuals' => collect($monthly)->filter(fn ($value) => $value !== null)->isNotEmpty(),
            'rangeLabel' => $start->format('M Y').' → '.$end->format('M Y'),
        ];
    }

    /**
     * @param  list<float>  $results
     */
    private function resultTrend(array $results): string
    {
        if (count($results) < 2) {
            return 'stable';
        }

        $previous = $results[count($results) - 2];
        $latest = $results[count($results) - 1];
        $delta = $latest - $previous;

        if (abs($delta) < 0.0001) {
            return 'stable';
        }

        return $delta > 0 ? 'up' : 'down';
    }

    public function edit(Kpi $kpi): View
    {
        $this->authorizeManage();

        $kpi->load(['projects', 'users']);

        return view('kpis.edit', array_merge($this->formData($kpi), compact('kpi')));
    }

    public function update(Request $request, Kpi $kpi): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $this->validateKpi($request, $kpi);
        $projectIds = $this->validatedProjectIds($request);
        $assignments = $this->validatedAssignments($request, $kpi);
        $before = $this->activityLogger->snapshot($kpi);
        $beforeProjects = $kpi->projects()->pluck('projects.id')->all();
        $beforeAssignments = $this->activityLogger->pivotAssignments($kpi->users()->get(), ['weightage']);

        $kpi->update($validated);
        $kpi->projects()->sync($projectIds);
        $kpi->users()->sync($assignments);
        $kpi->load(['category', 'projects', 'users']);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $kpi,
            description: 'Updated KPI "'.$kpi->name.'"',
            module: 'kpis',
            properties: [
                ...$this->activityLogger->diff($before, $this->activityLogger->snapshot($kpi)),
                'category' => $kpi->category?->name,
                'formula' => $kpi->formula,
                'formula_fields' => $kpi->formulaFieldDefinitions(),
                'projects_before' => $beforeProjects,
                'projects_after' => $kpi->projects->map(fn ($project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                ])->values()->all(),
                'assignments_before' => $beforeAssignments,
                'assignments_after' => $this->activityLogger->pivotAssignments($kpi->users, ['weightage']),
            ],
        );

        return redirect()
            ->route('kpis.show', $kpi)
            ->with('success', 'KPI updated successfully.');
    }

    public function destroy(Request $request, Kpi $kpi): RedirectResponse
    {
        $this->authorizeManage();

        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can delete KPIs.');
        }

        $validated = $request->validate([
            'confirm_a' => ['required', 'integer', 'min:1', 'max:12'],
            'confirm_b' => ['required', 'integer', 'min:1', 'max:12'],
            'confirm_answer' => ['required', 'integer'],
        ]);

        if ((int) $validated['confirm_a'] + (int) $validated['confirm_b'] !== (int) $validated['confirm_answer']) {
            $this->activityLogger->forModel(
                action: 'delete_verification_failed',
                subject: $kpi,
                description: 'Failed math verification while deleting KPI "'.$kpi->name.'"',
                module: 'kpis',
                properties: [
                    'confirm_a' => $validated['confirm_a'],
                    'confirm_b' => $validated['confirm_b'],
                    'confirm_answer' => $validated['confirm_answer'],
                ],
            );

            return back()->with('error', 'Incorrect verification answer. KPI was not deleted.');
        }

        $name = $kpi->name;
        $snapshot = $this->activityLogger->snapshot($kpi);
        $related = [
            'formula' => $kpi->formula,
            'formula_fields' => $kpi->formulaFieldDefinitions(),
            'projects' => $kpi->projects()->pluck('projects.name', 'projects.id')->all(),
            'assignments' => $this->activityLogger->pivotAssignments($kpi->users()->get(), ['weightage']),
            'results_count' => $kpi->results()->count(),
        ];
        $kpi->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted KPI "'.$name.'"',
            module: 'kpis',
            properties: [
                'attributes' => $snapshot,
                ...$related,
            ],
        );

        return redirect()
            ->route('kpis.index')
            ->with('success', 'KPI deleted successfully.');
    }

    private function formData(?Kpi $kpi = null): array
    {
        $selectedProjects = old('project_ids', $kpi?->projects->pluck('id')->all() ?? []);
        $selectedAssignments = [];

        if (old('assignments') !== null) {
            foreach (old('assignments', []) as $row) {
                $selectedAssignments[] = [
                    'user_id' => (string) ($row['user_id'] ?? ''),
                    'weightage' => (float) ($row['weightage'] ?? 0),
                ];
            }
        } elseif ($kpi) {
            foreach ($kpi->users as $user) {
                $selectedAssignments[] = [
                    'user_id' => (string) $user->id,
                    'weightage' => (float) $user->pivot->weightage,
                ];
            }
        }

        $assignableUsers = User::query()
            ->where('role', '!=', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->with(['designation', 'department'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($kpi) {
                $used = (float) DB::table('kpi_user')
                    ->where('user_id', $user->id)
                    ->when($kpi, fn ($q) => $q->where('kpi_id', '!=', $kpi->id))
                    ->sum('weightage');

                return [
                    'id' => $user->id,
                    'name' => $user->displayName(),
                    'meta' => trim(($user->designation?->name ?? '').($user->department ? ' · '.$user->department->name : '')),
                    'used_weightage' => round($used, 2),
                    'available_weightage' => round(max(0, 100 - $used), 2),
                ];
            })
            ->values()
            ->all();

        return [
            'categories' => KpiCategory::ordered()->get(),
            'projects' => Project::query()->with(['company', 'plant'])->orderByDesc('year')->orderBy('name')->get(),
            'assignableUsers' => $assignableUsers,
            'benchmarkTypes' => BenchmarkType::options(),
            'selectedProjects' => collect($selectedProjects)->map(fn ($id) => (string) $id)->values()->all(),
            'selectedAssignments' => $selectedAssignments,
            'formulaFields' => $this->initialFormulaFields($kpi),
        ];
    }

    /**
     * @return list<array{name: string}>
     */
    private function initialFormulaFields(?Kpi $kpi = null): array
    {
        if (old('formula_fields') !== null) {
            return collect(old('formula_fields', []))
                ->map(fn ($row) => ['name' => trim((string) ($row['name'] ?? ''))])
                ->filter(fn ($row) => $row['name'] !== '')
                ->values()
                ->all();
        }

        if ($kpi) {
            return $kpi->formulaFieldDefinitions();
        }

        return [];
    }

    private function validateKpi(Request $request, ?Kpi $kpi = null): array
    {
        if (! $request->filled('formula_fields') && $request->filled('formula_fields_payload')) {
            $payload = json_decode((string) $request->input('formula_fields_payload'), true);
            if (is_array($payload)) {
                $request->merge([
                    'formula_fields' => collect($payload)
                        ->map(fn ($name) => ['name' => trim((string) $name)])
                        ->filter(fn ($row) => $row['name'] !== '')
                        ->values()
                        ->all(),
                ]);
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kpi_index' => ['required', 'string', 'max:100'],
            'kpi_category_id' => ['required', 'exists:kpi_categories,id'],
            'definition' => ['nullable', 'string', 'max:5000'],
            'formula' => ['required', 'string', 'max:1000'],
            'formula_fields' => ['required', 'array', 'min:1'],
            'formula_fields.*.name' => ['required', 'string', 'max:100', 'distinct'],
            'benchmark_percent' => ['required', 'numeric', 'min:0', 'max:1000'],
            'benchmark_type' => ['required', Rule::in(array_keys(BenchmarkType::options()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fields = collect($validated['formula_fields'])
            ->map(fn ($row) => ['name' => trim((string) $row['name'])])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values()
            ->all();

        if ($fields === []) {
            throw ValidationException::withMessages([
                'formula_fields' => 'Add at least one value name.',
            ]);
        }

        if ($kpi && ! $request->user()?->isSuperAdmin()) {
            $existingNames = collect($kpi->formulaFieldDefinitions())->pluck('name')->all();
            $newNames = collect($fields)->pluck('name')->all();
            $removed = array_values(array_diff($existingNames, $newNames));
            $added = array_values(array_diff($newNames, $existingNames));

            // Renaming looks like remove + add in the same request.
            if ($removed !== [] && $added !== []) {
                throw ValidationException::withMessages([
                    'formula_fields' => 'Only Super Admin can rename value names.',
                ]);
            }
        }

        try {
            FormulaEvaluator::validateTemplate($validated['formula'], $fields);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'formula' => $e->getMessage(),
            ]);
        }

        // Persist the same normalized formula/names used for validation.
        $validated['formula'] = FormulaEvaluator::normalize($validated['formula']);
        $fields = collect(FormulaEvaluator::normalizeFieldNames($fields))
            ->map(fn (string $name) => ['name' => $name])
            ->values()
            ->all();
        $validated['formula_fields'] = $fields;
        $fieldNames = collect($fields)->pluck('name')->all();

        // Keep previously saved values only when field names still match.
        if ($kpi) {
            $oldValues = $kpi->formula_values ?? [];
            $kept = [];

            foreach ($fieldNames as $fieldName) {
                if (array_key_exists($fieldName, $oldValues) && is_numeric($oldValues[$fieldName])) {
                    $kept[$fieldName] = (float) $oldValues[$fieldName];
                }
            }

            if (count($kept) === count($fieldNames) && $kept !== []) {
                $validated['formula_values'] = $kept;
                $validated['formula_result'] = FormulaEvaluator::evaluate($validated['formula'], $kept);
            } else {
                $validated['formula_values'] = null;
                $validated['formula_result'] = null;
            }
        } else {
            $validated['formula_values'] = null;
            $validated['formula_result'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    public function calculate(Request $request, Kpi $kpi): RedirectResponse
    {
        $this->authorizeView($kpi);

        $variables = $kpi->formulaVariables();

        $validated = $request->validate([
            'recorded_on' => [
                'required',
                'date',
                'after_or_equal:'.$kpi->start_date->format('Y-m-d'),
                'before_or_equal:'.$kpi->end_date->format('Y-m-d'),
            ],
            'values' => ['required', 'array'],
        ]);

        $posted = $validated['values'];
        $errors = [];
        $values = [];

        foreach ($variables as $variable) {
            if (! array_key_exists($variable, $posted) || $posted[$variable] === '' || $posted[$variable] === null) {
                $errors['values.'.$variable] = "{$variable} is required.";
                continue;
            }

            if (! is_numeric($posted[$variable])) {
                $errors['values.'.$variable] = "{$variable} must be a number.";
                continue;
            }

            $values[$variable] = (float) $posted[$variable];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        try {
            $result = FormulaEvaluator::evaluate($kpi->formula, $values);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'values' => $e->getMessage(),
            ]);
        }

        $entry = KpiResult::create([
            'kpi_id' => $kpi->id,
            'recorded_on' => $validated['recorded_on'],
            'values' => $values,
            'result' => $result,
            'formula_snapshot' => $kpi->formula,
            'created_by' => $request->user()->id,
        ]);

        $kpi->update([
            'formula_values' => $values,
            'formula_result' => $result,
        ]);

        $source = $request->input('redirect_to') === 'index' ? 'quick_feed' : 'kpi_detail';

        $this->activityLogger->forModel(
            action: 'result_saved',
            subject: $entry,
            description: 'Fed data to KPI "'.$kpi->name.'" for '.$validated['recorded_on'].' (result: '.$result.')',
            module: 'kpis',
            properties: [
                'kpi_id' => $kpi->id,
                'kpi_name' => $kpi->name,
                'result_id' => $entry->id,
                'recorded_on' => $validated['recorded_on'],
                'formula' => $kpi->formula,
                'formula_fields' => $kpi->formulaFieldDefinitions(),
                'values' => $values,
                'result' => $result,
                'source' => $source,
            ],
        );

        return redirect()
            ->to($source === 'quick_feed' ? route('kpis.index') : route('kpis.show', $kpi))
            ->with('success', 'KPI result added to history for '.$validated['recorded_on'].' (result: '.$result.').');
    }

    public function destroyResult(Kpi $kpi, KpiResult $result): RedirectResponse
    {
        $this->authorizeManage();

        if ($result->kpi_id !== $kpi->id) {
            abort(404);
        }

        $date = $result->recorded_on->format('Y-m-d');
        $snapshot = $this->activityLogger->snapshot($result);
        $resultValues = $result->values;
        $resultValue = $result->result;
        $result->delete();

        $latest = $kpi->results()->reorder()->orderByDesc('id')->first();
        $kpi->update([
            'formula_values' => $latest?->values,
            'formula_result' => $latest?->result,
        ]);

        $this->activityLogger->forModel(
            action: 'result_deleted',
            subject: $kpi,
            description: 'Deleted KPI "'.$kpi->name.'" result for '.$date.' (was '.$resultValue.')',
            module: 'kpis',
            properties: [
                'attributes' => $snapshot,
                'recorded_on' => $date,
                'values' => $resultValues,
                'result' => $resultValue,
                'formula' => $kpi->formula,
            ],
        );

        return redirect()
            ->route('kpis.show', $kpi)
            ->with('success', 'Result history entry deleted.');
    }

    private function validatedProjectIds(Request $request): array
    {
        $request->validate([
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'exists:projects,id'],
        ]);

        return collect($request->input('project_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function validatedAssignments(Request $request, ?Kpi $kpi = null): array
    {
        $request->validate([
            'assignments' => ['nullable', 'array'],
            'assignments.*.user_id' => ['required', 'distinct', 'exists:users,id'],
            'assignments.*.weightage' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ]);

        $assignments = [];

        foreach ($request->input('assignments', []) as $row) {
            $userId = (int) $row['user_id'];
            $weightage = round((float) $row['weightage'], 2);
            $user = User::find($userId);

            if ($user?->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'assignments' => 'Super Admin accounts cannot be assigned to KPIs.',
                ]);
            }

            $usedElsewhere = (float) DB::table('kpi_user')
                ->where('user_id', $userId)
                ->when($kpi, fn ($q) => $q->where('kpi_id', '!=', $kpi->id))
                ->sum('weightage');

            $available = round(100 - $usedElsewhere, 2);

            if ($weightage > $available + 0.001) {
                throw ValidationException::withMessages([
                    'assignments' => ($user?->displayName() ?? 'User').' only has '.$available.'% weightage remaining (already using '.$usedElsewhere.'% on other KPIs).',
                ]);
            }

            $assignments[$userId] = ['weightage' => $weightage];
        }

        return $assignments;
    }

    private function authorizeView(Kpi $kpi): void
    {
        abort_unless($kpi->canBeViewedBy(auth()->user()), 403);
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->canManageUsers(), 403);
    }
}
