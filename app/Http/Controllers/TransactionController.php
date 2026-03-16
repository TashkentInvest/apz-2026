<?php

namespace App\Http\Controllers;

use App\Services\SimpleXlsxExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    const CACHE_REPORT  = 3600;   // 1 hour
    const CACHE_FILTERS = 900;    // 15 minutes

    // ──────────────────────────────────────────────────────────────────────
    // APZ PAYMENTS LIST  (was: index)
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $allowedSorts = ['p.id', 'p.payment_date', 'p.district', 'p.type', 'p.flow', 'p.amount'];
        $sortField    = in_array('p.' . $request->sort, $allowedSorts) ? 'p.' . $request->sort : 'p.id';
        $sortDir      = $request->dir === 'asc' ? 'ASC' : 'DESC';

        $where  = [];
        $params = [];

        if ($request->filled('district'))  { $where[] = 'p.district = ?';      $params[] = $request->district; }
        if ($request->filled('year'))      { $where[] = 'p.year = ?';           $params[] = $request->year; }
        if ($request->filled('month'))     { $where[] = 'p.month = ?';          $params[] = $request->month; }
        if ($request->filled('type'))      { $where[] = 'p.type = ?';           $params[] = $request->type; }
        if ($request->filled('date_from')) { $where[] = 'p.payment_date >= ?';  $params[] = $request->date_from; }
        if ($request->filled('date_to'))   { $where[] = 'p.payment_date <= ?';  $params[] = $request->date_to; }
        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $where[] = '(p.district LIKE ? OR p.company_name LIKE ? OR p.payment_purpose LIKE ? OR c.investor_name LIKE ?)';
            $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        if ($this->isXlsxExportRequest($request)) {
            return $this->exportUnifiedSystemXlsx($request, $whereSQL, $params);
        }

        $total = DB::selectOne(
            "SELECT COUNT(*) as cnt
             FROM apz_payments p
             LEFT JOIN apz_contracts c ON c.contract_id = p.contract_id
             {$whereSQL}",
            $params
        )->cnt;

        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;

        $rows = DB::select(
            "SELECT p.id, p.payment_date, p.contract_id, p.district, p.type, p.flow,
                    p.amount, p.year, p.month, p.company_name, p.inn,
                    p.payment_purpose, p.debit_amount, p.credit_amount,
                    c.investor_name, c.contract_number, c.contract_status
             FROM apz_payments p
             LEFT JOIN apz_contracts c ON c.contract_id = p.contract_id
             {$whereSQL}
             ORDER BY {$sortField} {$sortDir}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $filters = Cache::remember('apz_filters', self::CACHE_FILTERS, function () {
            $rows = DB::select(
                'SELECT DISTINCT p.district, p.year, p.month, p.type
                 FROM apz_payments p
                 ORDER BY p.district, p.year'
            );
            $d = $y = $m = $t = [];
            foreach ($rows as $r) {
                if ($r->district) $d[$r->district] = true;
                if ($r->year)     $y[$r->year]     = true;
                if ($r->month)    $m[$r->month]    = true;
                if ($r->type)     $t[$r->type]     = true;
            }
            return [
                'districts' => array_keys($d),
                'years'     => array_keys($y),
                'months'    => array_keys($m),
                'types'     => array_keys($t),
            ];
        });

        $summary = Cache::remember('apz_summary', self::CACHE_FILTERS, function () {
            return (array) DB::selectOne(
                "SELECT
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN flow='Расход' THEN amount ELSE 0 END) as total_expense,
                    COUNT(*) as total_records
                 FROM apz_payments"
            );
        });

        return view('transactions.index', [
            'transactions' => $transactions,
            'districts'    => $filters['districts'],
            'years'        => $filters['years'],
            'months'       => $filters['months'],
            'types'        => $filters['types'],
            'summary'      => $summary,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DASHBOARD  — APZ overview with charts
    // ──────────────────────────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        $monitorDistrict = $request->filled('district') ? trim((string) $request->district) : null;
        $monitorStatus   = $this->normalizeRequestedStatus($request->filled('status') ? (string) $request->status : 'all');
        $monitorIssue    = $this->normalizeRequestedIssue($request->filled('issue') ? (string) $request->issue : 'all');
        $monitorSearch   = $request->filled('search') ? trim((string) $request->search) : null;

        $dashboardCacheKey = 'apz_dashboard_data_v6_' . md5(
            ($monitorDistrict ?? 'all') . '|' .
            $monitorStatus . '|' .
            $monitorIssue . '|' .
            mb_strtolower($monitorSearch ?? '')
        );

        $viewData = Cache::remember($dashboardCacheKey, self::CACHE_REPORT, function () use ($monitorDistrict, $monitorStatus, $monitorIssue, $monitorSearch) {

            // Overall stats
            $global = DB::selectOne("
                SELECT
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN flow='Расход' THEN amount ELSE 0 END) as total_expense,
                    COUNT(*) as total_records,
                    COUNT(DISTINCT district) as unique_districts,
                    COUNT(DISTINCT contract_id) as unique_contracts
                FROM apz_payments
            ");

            // Contract stats
            $statusExpr = "LOWER(TRIM(COALESCE(contract_status, '')))";
            $cancelledExpr = "({$statusExpr} LIKE '%bekor%' OR {$statusExpr} LIKE '%бекор%' OR {$statusExpr} LIKE '%cancel%')";
            $completedExpr = "({$statusExpr} LIKE '%yakun%' OR {$statusExpr} LIKE '%якун%' OR {$statusExpr} LIKE '%tugal%' OR {$statusExpr} LIKE '%complete%')";

            $statusExprAlias = "LOWER(TRIM(COALESCE(c.contract_status, '')))";
            $cancelledExprAlias = "({$statusExprAlias} LIKE '%bekor%' OR {$statusExprAlias} LIKE '%бекор%' OR {$statusExprAlias} LIKE '%cancel%')";
            $completedExprAlias = "({$statusExprAlias} LIKE '%yakun%' OR {$statusExprAlias} LIKE '%якун%' OR {$statusExprAlias} LIKE '%tugal%' OR {$statusExprAlias} LIKE '%complete%')";

            $contractStats = DB::selectOne("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN {$completedExpr} THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN {$cancelledExpr} THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN (NOT {$cancelledExpr} AND NOT {$completedExpr}) THEN 1 ELSE 0 END) as active,
                    SUM(contract_value) as total_value
                FROM apz_contracts
            ");

            $activeContracts = $this->fetchContractFinancialRows([
                "(NOT {$cancelledExprAlias} AND NOT {$completedExprAlias})",
            ]);
            $debtorsStats = $this->buildOverdueDebtStatsFromContracts($activeContracts);

            // Monthly income — last 18 months
            $monthlyStats = DB::select("
                SELECT year, month,
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) as income,
                    SUM(CASE WHEN flow='Расход' THEN amount ELSE 0 END) as expense,
                    COUNT(*) as cnt
                FROM apz_payments
                WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 18 MONTH)
                GROUP BY year, month
                ORDER BY year DESC,
                    FIELD(month,'Январь','Февраль','Март','Апрель','Май','Июнь',
                                'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь') DESC
                LIMIT 18
            ");

            // District stats
            $districtStats = DB::select("
                SELECT district,
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) as income,
                    COUNT(*) as cnt
                FROM apz_payments
                WHERE district IS NOT NULL AND district != ''
                GROUP BY district
                ORDER BY income DESC
            ");

            // Payment type breakdown
            $typeStats = DB::select("
                SELECT type,
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) as income,
                    COUNT(*) as cnt
                FROM apz_payments
                WHERE type IS NOT NULL AND type != ''
                GROUP BY type
                ORDER BY income DESC
            ");

            // Plan vs Fact — contracts with total planned vs total paid (joined)
            $planFact = DB::select("
                SELECT c.district,
                    SUM(c.contract_value) as plan_total,
                    SUM(COALESCE(paid.total_paid, 0)) as fact_total
                FROM apz_contracts c
                LEFT JOIN (
                    SELECT contract_id, SUM(amount) as total_paid
                    FROM apz_payments
                    WHERE flow = 'Приход'
                    GROUP BY contract_id
                ) paid ON paid.contract_id = c.contract_id
                WHERE c.district IS NOT NULL AND c.district != ''
                GROUP BY c.district
                ORDER BY plan_total DESC
            ");

            // ── Reuse summary() drill-down data (all years) ────────────────
            // Call summary logic with year=null to get full drill-down dataset
            $drillDown = $this->buildSummaryDrillDown(null); // null = all years
            $monitoring = $this->buildMonitoringData([
                'district' => $monitorDistrict,
                'status'   => $monitorStatus,
                'issue'    => $monitorIssue,
                'search'   => $monitorSearch,
            ]);

            // ── 8. Available years ────────────────────────────────────────
            $availableYears = DB::table('apz_payments')
                ->selectRaw('DISTINCT year')->whereNotNull('year')->where('year', '>', 0)
                ->orderBy('year', 'desc')->pluck('year')->toArray();

            $availableDistricts = DB::table('apz_contracts')
                ->selectRaw('DISTINCT district')
                ->whereNotNull('district')->where('district', '!=', '')
                ->orderBy('district')
                ->pluck('district')
                ->toArray();

            return array_merge($drillDown, compact(
                'global', 'contractStats', 'debtorsStats',
                'monthlyStats', 'districtStats', 'typeStats', 'planFact', 'availableDistricts'
            ), $monitoring);
        });

        // Merge drill-down data from summary cache
        $drillDownData = Cache::remember('apz_summary_v3_all', self::CACHE_REPORT, function () {
            return $this->buildSummaryDrillDown(null);
        });
        $viewData = array_merge($viewData, $drillDownData);

        $monitoringDistrictCount = is_array($viewData['monitoringDistrictRows'] ?? null)
            ? count($viewData['monitoringDistrictRows'])
            : 0;

        $viewData['dashboardDistrictCount'] = $monitoringDistrictCount > 0
            ? $monitoringDistrictCount
            : (int) (($viewData['global']->unique_districts ?? 0));

        $viewData['selectedMonitoringDistrict'] = $monitorDistrict;
        $viewData['selectedMonitoringStatus']   = $monitorStatus;
        $viewData['selectedMonitoringIssue']    = $monitorIssue;
        $viewData['monitoringSearch']           = $monitorSearch;

        $this->ensureDashboardMonitoringLinks($viewData, $monitorDistrict, $monitorStatus, $monitorIssue, $monitorSearch);

        if ($this->isXlsxExportRequest($request)) {
            return $this->exportDashboardXlsx($viewData);
        }

        return view('transactions.dashboard', $viewData);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SUMMARY — APZ payments by district and type  (Свод)
    // ──────────────────────────────────────────────────────────────────────
    public function summary(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : null;

        $cacheKey = 'apz_summary_v3_' . ($year ?? 'all');

        $viewData = Cache::remember($cacheKey, self::CACHE_REPORT, function () use ($year) {
            return $this->buildSummaryDrillDown($year);
        });

        $viewData['selectedYear'] = $year;
        return view('transactions.summary', $viewData);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SUMMARY2 — Plan vs Fact monthly timeline per contract
    // ──────────────────────────────────────────────────────────────────────
    public function summary2(Request $request)
    {
        $district = $request->filled('district') ? $request->district : null;
        $statusInput = $request->filled('status') ? (string) $request->status : 'all';
        $status      = $this->normalizeRequestedStatus($statusInput);
        $issueInput  = $request->filled('issue') ? (string) $request->issue : 'all';
        $issue       = $this->normalizeRequestedIssue($issueInput);
        $searchTerm  = $request->filled('search') ? trim((string) $request->search) : null;
        $page     = max(1, (int) $request->get('page', 1));
        $perPage  = 25;
        $offset   = ($page - 1) * $perPage;

        $cacheKey = 'apz_summary2_' . md5(
            ($district ?? 'all') . '|' .
            $status . '|' .
            $issue . '|' .
            mb_strtolower($searchTerm ?? '')
        );

        $allData = Cache::remember($cacheKey, self::CACHE_REPORT, function () use ($district, $status, $issue, $searchTerm) {

            $cWhere = [];
            if ($district) $cWhere[] = "c.district = " . DB::getPdo()->quote($district);
            $statusWhere = $this->buildContractStatusWhereSql($status, 'c');
            if ($statusWhere) $cWhere[] = $statusWhere;
            $issueWhere = $this->buildConstructionIssueWhereSql($issue, 'c');
            if ($issueWhere) $cWhere[] = $issueWhere;
            if ($searchTerm) {
                $q = DB::getPdo()->quote('%' . $searchTerm . '%');
                $cWhere[] = "(c.investor_name LIKE {$q} OR c.contract_number LIKE {$q} OR c.inn LIKE {$q} OR CAST(c.contract_id AS CHAR) LIKE {$q})";
            }
            $cSQL = $cWhere ? 'WHERE ' . implode(' AND ', $cWhere) : '';

            $contracts = DB::select("
                SELECT c.id, c.contract_id, c.district, c.investor_name, c.inn,
                       c.contract_number, c.contract_date, c.contract_status,
                       c.construction_issues,
                      c.contract_value, c.payment_terms, c.installments_count, c.payment_schedule,
                       COALESCE(paid.total_paid, 0) as total_paid,
                       COALESCE(paid.payment_count, 0) as payment_count
                FROM apz_contracts c
                LEFT JOIN (
                    SELECT contract_id,
                           SUM(amount)  as total_paid,
                           COUNT(*)     as payment_count
                    FROM apz_payments
                    WHERE flow = 'Приход'
                    GROUP BY contract_id
                ) paid ON paid.contract_id = c.contract_id
                {$cSQL}
                ORDER BY c.contract_id
            ");

            foreach ($contracts as $contract) {
                $contract->status_key   = $this->normalizeContractStatus($contract->contract_status ?? null);
                $contract->status_label = $this->contractStatusLabel($contract->contract_status ?? null);
                $contract->issue_key    = $this->normalizeConstructionIssue($contract->construction_issues ?? null);
                $contract->issue_label  = $this->issueStatusLabel($contract->construction_issues ?? null);
            }

            $grandPlan = $grandFact = 0;
            foreach ($contracts as $c) {
                $grandPlan += (float) ($c->contract_value ?? 0);
                $grandFact += (float) $c->total_paid;
            }

            $availableDistricts = DB::table('apz_contracts')
                ->selectRaw('DISTINCT district')
                ->whereNotNull('district')->where('district', '!=', '')
                ->orderBy('district')
                ->pluck('district')
                ->toArray();

            return compact('contracts', 'grandPlan', 'grandFact', 'availableDistricts');
        });

        if ($this->isXlsxExportRequest($request)) {
            return $this->exportSummary2Xlsx(
                (array) ($allData['contracts'] ?? []),
                $district,
                $status,
                $issue,
                $searchTerm
            );
        }

        $allContracts = $allData['contracts'];
        $total        = count($allContracts);
        $lastPage     = (int) ceil($total / $perPage) ?: 1;
        $page         = min($page, $lastPage);
        $contracts    = array_slice($allContracts, ($page - 1) * $perPage, $perPage);

        $grandPlan = (float) ($allData['grandPlan'] ?? 0);
        $grandFact = (float) ($allData['grandFact'] ?? 0);
        $overallPct = $grandPlan > 0 ? round($grandFact / $grandPlan * 100, 1) : 0;

        $summaryRowBalance = $grandPlan - $grandFact;
        $summaryRowBarWidth = $grandPlan > 0 ? min((int) round($grandFact / $grandPlan * 100), 100) : 0;

        $paginationQuery = [
            'district' => $district,
            'status'   => $status !== 'all' ? $status : null,
            'issue'    => $issue !== 'all' ? $issue : null,
            'search'   => $searchTerm,
        ];

        return view('transactions.summary2', [
            'reportDate' => now()->format('d.m.Y'),
            'contracts' => $this->mapSummaryContractsForView($contracts, $page, $perPage, $request->fullUrl()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
            'summaryStats' => [
                'total_contracts' => $this->formatNumber($total),
                'grand_plan_mln' => $this->formatNumber($grandPlan, 1),
                'grand_fact_mln' => $this->formatNumber($grandFact, 1),
                'overall_pct' => $this->formatNumber($overallPct, 1),
                'overall_pct_class' => $overallPct >= 100 ? 'txt-good' : 'txt-warn',
            ],
            'summaryRow' => [
                'plan_mln' => $this->formatNumber($grandPlan, 2),
                'fact_mln' => $this->formatNumber($grandFact, 2),
                'balance_mln' => $this->formatNumber($summaryRowBalance, 2),
                'balance_class' => $grandPlan > $grandFact ? 'txt-danger' : 'txt-good',
                'progress_label' => $this->formatNumber($overallPct, 1) . '%',
                'progress_width' => $summaryRowBarWidth,
                'progress_class' => ($grandPlan > 0 && ($grandFact / $grandPlan) > 1) ? 'over' : '',
            ],
            'districtOptions' => $this->buildDistrictOptions($allData['availableDistricts'] ?? [], $district, 'Барча туман'),
            'statusOptions' => $this->buildStatusOptions($status),
            'issueOptions' => $this->buildIssueOptions($issue, 'Муаммо: барчаси'),
            'searchTerm' => $searchTerm,
            'selectedDistrict' => $district,
            'selectedStatus' => $status,
            'selectedIssue' => $issue,
            'showResetFilters' => ($district !== null || $status !== 'all' || $issue !== 'all' || !empty($searchTerm)),
            'pagination' => $this->buildPagination('summary2', $paginationQuery, $page, $lastPage),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DEBTS — Active/Completed/Cancelled contracts with debt calculation
    // ──────────────────────────────────────────────────────────────────────
    public function debts(Request $request)
    {
        $statusInput = $request->filled('status') ? (string) $request->status : 'in_progress';
        $status      = $this->normalizeRequestedStatus($statusInput, 'in_progress');
        $issueInput  = $request->filled('issue') ? (string) $request->issue : 'all';
        $issue       = $this->normalizeRequestedIssue($issueInput);
        $debtTypeInput = $request->filled('debt_type') ? (string) $request->debt_type : 'all';
        $debtType      = $this->normalizeDebtTypeFilter($debtTypeInput);
        $district    = $request->filled('district') ? trim((string) $request->district) : null;
        $searchTerm  = $request->filled('search') ? trim((string) $request->search) : null;
        $onlyDebtors = (int) $request->get('debtors', 0) === 1;
        $page        = max(1, (int) $request->get('page', 1));
        $perPage     = 25;

        $cacheKey = 'apz_debts_v4_' . md5(
            $status . '|' .
            $issue . '|' .
            $debtType . '|' .
            ($district ?? 'all') . '|' .
            mb_strtolower($searchTerm ?? '') . '|' .
            ($onlyDebtors ? 'debtors' : 'all')
        );

        $allData = Cache::remember($cacheKey, self::CACHE_REPORT, function () use ($status, $issue, $debtType, $district, $searchTerm, $onlyDebtors) {
            $where = [];
            $statusWhere = $this->buildContractStatusWhereSql($status, 'c');
            if ($statusWhere) $where[] = $statusWhere;
            $issueWhere = $this->buildConstructionIssueWhereSql($issue, 'c');
            if ($issueWhere) $where[] = $issueWhere;
            if ($district) $where[] = "c.district = " . DB::getPdo()->quote($district);
            if ($searchTerm) {
                $q = DB::getPdo()->quote('%' . $searchTerm . '%');
                $where[] = "(c.investor_name LIKE {$q} OR c.contract_number LIKE {$q} OR c.inn LIKE {$q} OR CAST(c.contract_id AS CHAR) LIKE {$q})";
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $contracts = DB::select("
                SELECT c.id, c.contract_id, c.investor_name, c.district,
                       c.contract_number, c.contract_date, c.contract_status,
                      c.construction_issues, c.contract_value, c.payment_terms, c.payment_schedule,
                       COALESCE(paid.total_paid, 0) as total_paid
                FROM apz_contracts c
                LEFT JOIN (
                    SELECT contract_id,
                           SUM(amount) as total_paid
                    FROM apz_payments
                    WHERE flow = 'Приход'
                    GROUP BY contract_id
                ) paid ON paid.contract_id = c.contract_id
                {$whereSql}
                ORDER BY c.contract_id
            ");

            $grandPlan = 0;
            $grandFact = 0;
            $grandDebt = 0;
            $grandUnoverdueDebt = 0;
            $preparedContracts = [];

            foreach ($contracts as $contract) {
                $metrics = $this->calculateContractFinancials(
                    $contract->contract_value ?? 0,
                    $contract->total_paid ?? 0,
                    $contract->payment_schedule ?? null,
                    $contract->payment_terms ?? null,
                    $contract->contract_date ?? null
                );
                $plan = $metrics['plan'];
                $fact = $metrics['fact'];
                $diff = $metrics['diff'];

                $contract->status_key      = $this->normalizeContractStatus($contract->contract_status ?? null);
                $contract->status_label    = $this->contractStatusLabel($contract->contract_status ?? null);
                $contract->issue_key       = $this->normalizeConstructionIssue($contract->construction_issues ?? null);
                $contract->issue_label     = $this->issueStatusLabel($contract->construction_issues ?? null);

                $overdueDebt = (float) ($metrics['overdue_debt'] ?? 0.0);
                $unoverdueDebt = (float) ($metrics['unoverdue_debt'] ?? 0.0);

                if ($debtType === 'overdue' && $overdueDebt <= 0.0) {
                    continue;
                }

                if ($debtType === 'unoverdue' && $unoverdueDebt <= 0.0) {
                    continue;
                }

                if ($debtType === 'overdue') {
                    $unoverdueDebt = 0.0;
                }

                if ($debtType === 'unoverdue') {
                    $overdueDebt = 0.0;
                }

                $contract->debt            = $overdueDebt;
                $contract->overdue_debt    = $overdueDebt;
                $contract->unoverdue_debt  = $unoverdueDebt;
                $contract->plan_fact_diff  = $diff;

                $totalDebt = $overdueDebt + $unoverdueDebt;

                if ($onlyDebtors && $totalDebt <= 0.0) {
                    continue;
                }

                $preparedContracts[] = $contract;

                $grandPlan += $plan;
                $grandFact += $fact;
                $grandDebt += $overdueDebt;
                $grandUnoverdueDebt += $unoverdueDebt;
            }

            $contracts = $preparedContracts;

            $availableDistricts = DB::table('apz_contracts')
                ->selectRaw('DISTINCT district')
                ->whereNotNull('district')->where('district', '!=', '')
                ->orderBy('district')
                ->pluck('district')
                ->toArray();

            return compact('contracts', 'grandPlan', 'grandFact', 'grandDebt', 'grandUnoverdueDebt', 'availableDistricts');
        });

        if ($this->isXlsxExportRequest($request)) {
            return $this->exportDebtsXlsx(
                (array) ($allData['contracts'] ?? []),
                $status,
                $issue,
                $district,
                $searchTerm,
                $debtType,
                $onlyDebtors
            );
        }

        $allContracts = $allData['contracts'];
        $total        = count($allContracts);
        $lastPage     = (int) ceil($total / $perPage) ?: 1;
        $page         = min($page, $lastPage);
        $contracts    = array_slice($allContracts, ($page - 1) * $perPage, $perPage);

        $grandPlan = (float) ($allData['grandPlan'] ?? 0);
        $grandFact = (float) ($allData['grandFact'] ?? 0);
        $grandDebt = (float) ($allData['grandDebt'] ?? 0);
        $grandUnoverdueDebt = (float) ($allData['grandUnoverdueDebt'] ?? 0);
        $grandTotalDebt = max($grandDebt + $grandUnoverdueDebt, 0);
        $overallPctValue = $grandPlan > 0 ? round(($grandFact / $grandPlan) * 100, 1) : 0.0;
        $overallPctClass = $overallPctValue >= 100
            ? 'txt-good'
            : ($overallPctValue >= 60 ? 'txt-pending' : 'txt-danger');
        $grandPct = $grandPlan > 0 ? round(($grandFact / $grandPlan) * 100, 1) : null;

        $paginationQuery = [
            'status' => $status,
            'district' => $district,
            'issue' => $issue !== 'all' ? $issue : null,
            'debt_type' => $debtType !== 'all' ? $debtType : null,
            'debtors' => $onlyDebtors ? 1 : null,
            'search' => $searchTerm,
        ];

        return view('transactions.debts', [
            'reportDate' => now()->format('d.m.Y'),
            'contracts' => $this->mapDebtContractsForView($contracts, $page, $perPage, $request->fullUrl()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
            'summaryStats' => [
                'total_contracts' => $this->formatNumber($total),
                'grand_plan_mln' => $this->formatNumber($grandPlan, 2),
                'grand_fact_mln' => $this->formatNumber($grandFact, 2),
                'grand_debt_mln' => $this->formatNumber($grandDebt, 2),
                'grand_unoverdue_debt_mln' => $this->formatNumber($grandUnoverdueDebt, 2),
                'grand_total_debt_mln' => $this->formatNumber($grandTotalDebt, 2),
                'overall_pct' => $this->formatNumber($overallPctValue, 1) . '%',
                'overall_pct_class' => $overallPctClass,
            ],
            'summaryRow' => [
                'plan_mln' => $this->formatNumber($grandPlan, 2),
                'fact_mln' => $this->formatNumber($grandFact, 2),
                'debt_mln' => $this->formatNumber($grandDebt, 2),
                'unoverdue_debt_mln' => $this->formatNumber($grandUnoverdueDebt, 2),
                'diff_mln' => $this->formatNumber(($grandPlan - $grandFact), 2),
                'diff_class' => $this->completionBandClass($grandPct),
            ],
            'districtOptions' => $this->buildDistrictOptions($allData['availableDistricts'] ?? [], $district, 'Туман: барчаси'),
            'statusOptions' => $this->buildStatusOptions($status),
            'issueOptions' => $this->buildIssueOptions($issue, 'Муаммо ҳолати: барчаси'),
            'debtTypeOptions' => $this->buildDebtTypeOptions($debtType),
            'debtorsOptions' => $this->buildDebtorOptions($onlyDebtors),
            'selectedStatus' => $status,
            'selectedIssue' => $issue,
            'selectedDebtType' => $debtType,
            'selectedDistrict' => $district,
            'searchTerm' => $searchTerm,
            'onlyDebtors' => $onlyDebtors,
            'showResetFilters' => ($status !== 'in_progress' || $issue !== 'all' || $debtType !== 'all' || $district !== null || !empty($searchTerm) || $onlyDebtors),
            'pagination' => $this->buildPagination('debts', $paginationQuery, $page, $lastPage),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // CONTRACT SHOW — Full details page
    // ──────────────────────────────────────────────────────────────────────
    public function contractShow(Request $request, $contractId)
    {
        $contractId = (int) $contractId;
        $page       = max(1, (int) $request->get('page', 1));
        $perPage    = 20;

        $payload = $this->getContractDetailPayload($contractId, $page, $perPage);
        if (!$payload) {
            abort(404);
        }

        $contract = $payload['contract'];
        $metrics  = $this->calculateContractFinancials(
            $contract->contract_value ?? 0,
            $contract->total_paid ?? 0,
            $contract->payment_schedule ?? null,
            $contract->payment_terms ?? null,
            $contract->contract_date ?? null
        );
        $plan     = $metrics['plan'];
        $fact     = $metrics['fact'];

        $contract->status_key   = $this->normalizeContractStatus($contract->contract_status ?? null);
        $contract->status_label = $this->contractStatusLabel($contract->contract_status ?? null);
        $contract->issue_key    = $this->normalizeConstructionIssue($contract->construction_issues ?? null);
        $contract->issue_label  = $this->issueStatusLabel($contract->construction_issues ?? null);
        $contract->debt         = $metrics['debt'];
        $contract->pct          = $metrics['pct'];

        $backUrl = $request->filled('back') ? (string) $request->back : null;

        $advancePercent = $this->extractInitialPaymentPercent($contract->payment_terms ?? null);
        $advanceAmount = $plan > 0 ? ($plan * $advancePercent) / 100 : 0.0;
        $advancePercentDecimals = abs($advancePercent - floor($advancePercent)) < 0.00001 ? 0 : 2;
        $advanceLabel = $advancePercent > 0
            ? $this->formatNumber($advancePercent, $advancePercentDecimals) . '% · ' . $this->formatNumber($advanceAmount, 2)
            : '—';

        $scheduleRows = [];
        $scheduleEditorRows = [];
        $remainingFactForSchedule = $fact;
        $today = \Carbon\CarbonImmutable::today();

        foreach ($payload['schedule'] as $index => $row) {
            $scheduleAmount = max((float) ($row['amount'] ?? 0), 0.0);
            $factAmount = min($scheduleAmount, max($remainingFactForSchedule, 0.0));
            $remainingFactForSchedule = max($remainingFactForSchedule - $factAmount, 0.0);

            $type = 'Муддатсиз';
            $rawDate = trim((string) ($row['date'] ?? ''));
            if ($rawDate !== '' && $rawDate !== '—') {
                try {
                    $dueDate = \Carbon\CarbonImmutable::createFromFormat('d.m.Y', $rawDate)->endOfDay();
                    $type = $dueDate->lessThan($today) ? 'Муддатли' : 'Муддатсиз';
                } catch (\Throwable $e) {
                    try {
                        $dueDate = \Carbon\CarbonImmutable::parse($rawDate)->endOfDay();
                        $type = $dueDate->lessThan($today) ? 'Муддатли' : 'Муддатсиз';
                    } catch (\Throwable $inner) {
                    }
                }
            }

            $isFutureSchedule = $type === 'Муддатсиз';

            $diffAmount = max($scheduleAmount - $factAmount, 0.0);
            $diffPercent = null;
            $diffPercentClass = 'pct-none';

            if (!$isFutureSchedule && $scheduleAmount > 0.0) {
                $diffPercent = max(min(($factAmount / $scheduleAmount) * 100, 100), 0);

                if ($diffPercent < 10) {
                    $diffPercentClass = 'pct-red';
                } elseif ($diffPercent < 35) {
                    $diffPercentClass = 'pct-orange';
                } elseif ($diffPercent < 60) {
                    $diffPercentClass = 'pct-yellow';
                } else {
                    $diffPercentClass = 'pct-green';
                }
            }

            if ($isFutureSchedule) {
                $diffPercentClass = 'pct-green';
            }

            $diffClass = $diffAmount > 0.0 ? 'flow-out' : 'flow-in';
            if ($isFutureSchedule) {
                $diffClass = 'flow-in';
            }

            $scheduleRows[] = [
                'row_num' => $index + 1,
                'type' => $type,
                'date' => $row['date'] ?? '—',
                'schedule_amount' => $this->formatNumber($scheduleAmount, 2),
                'fact_amount' => $this->formatNumber($factAmount, 2),
                'diff_amount' => $this->formatNumber($diffAmount, 2),
                'diff_class' => $diffClass,
                'diff_pct' => $diffPercent === null ? '—' : $this->formatNumber($diffPercent, 1) . '%',
                'diff_pct_class' => $diffPercentClass,
            ];

            $editorDateValue = '';
            $editorDateRaw = trim((string) ($row['date'] ?? ''));
            if ($editorDateRaw !== '' && $editorDateRaw !== '—') {
                try {
                    $editorDateValue = \Carbon\CarbonImmutable::createFromFormat('d.m.Y', $editorDateRaw)->format('Y-m-d');
                } catch (\Throwable $e) {
                    try {
                        $editorDateValue = \Carbon\CarbonImmutable::parse($editorDateRaw)->format('Y-m-d');
                    } catch (\Throwable $inner) {
                    }
                }
            }

            $scheduleEditorRows[] = [
                'row_num' => $index + 1,
                'date' => $editorDateValue,
                'amount' => number_format($scheduleAmount, 2, '.', ''),
            ];
        }

        $statusKey = (string) ($contract->status_key ?? 'in_progress');
        $issueKey = (string) ($contract->issue_key ?? 'unknown');

        return view('transactions.contract-show', [
            'reportDate' => now()->format('d.m.Y'),
            'contract' => [
                'contract_id' => (int) ($contract->contract_id ?? 0),
                'contract_number' => $contract->contract_number ?: (string) ($contract->contract_id ?? '—'),
                'investor_name' => $contract->investor_name ?: '—',
                'district' => $contract->district ?: '—',
                'mfy' => $contract->mfy ?: '—',
                'address' => $contract->address ?: '—',
                'build_volume' => $contract->build_volume ? $this->formatNumber($contract->build_volume, 2) . ' м³' : '—',
                'coefficient' => $contract->coefficient ?: '—',
                'zone' => $contract->zone ?: '—',
                'permit' => $contract->permit ?: '—',
                'apz_number' => $contract->apz_number ?: '—',
                'council_decision' => $contract->council_decision ?: '—',
                'expertise' => $contract->expertise ?: '—',
                'inn' => $contract->inn ?: '—',
                'contract_date' => $this->formatDate($contract->contract_date ?? null),
                'status_label' => $contract->status_label ?? 'Амалдаги',
                'status_class' => $this->statusClass($statusKey, 'st-inprogress'),
                'issue_label' => $contract->issue_label ?? '—',
                'issue_class' => str_replace('issue-', 'is-', $this->issueClass($issueKey)),
                'plan_mln' => $this->formatNumber($plan, 2),
                'advance_label' => $advanceLabel,
                'fact_mln' => $this->formatNumber($fact, 2),
                'debt_mln' => $this->formatNumber(((float) ($contract->debt ?? 0)), 2),
                'pct' => $this->formatNumber((float) ($contract->pct ?? 0), 1),
            ],
            'scheduleRows' => $scheduleRows,
            'scheduleEditorRows' => $scheduleEditorRows,
            'payments' => $this->mapContractPaymentsForView($payload['payments'], (int) $payload['page'], (int) $payload['per_page']),
            'total' => (int) ($payload['total'] ?? 0),
            'lastPage' => (int) ($payload['last_page'] ?? 1),
            'backUrl' => $backUrl ?: route('summary2'),
            'canEditSchedule' => $this->canEditContractSchedule($request->user()),
            'pagination' => $this->buildContractPagination((int) ($contract->contract_id ?? 0), $backUrl, (int) $payload['page'], (int) $payload['last_page']),
        ]);
    }

    public function updateContractSchedule(Request $request, $contractId)
    {
        if (!$this->canEditContractSchedule($request->user())) {
            abort(403, 'Forbidden');
        }

        $contractId = (int) $contractId;

        $contractExists = DB::table('apz_contracts')
            ->where('contract_id', $contractId)
            ->exists();

        if (!$contractExists) {
            abort(404);
        }

        $rows = $request->input('schedule', []);
        if (!is_array($rows)) {
            $rows = [];
        }

        $normalizedSchedule = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $dateRaw = trim((string) ($row['date'] ?? ''));
            $amountRaw = trim((string) ($row['amount'] ?? ''));

            if ($dateRaw === '' && $amountRaw === '') {
                continue;
            }

            if ($dateRaw === '' || $amountRaw === '') {
                return back()
                    ->withInput()
                    ->with('error', 'График санаси ва график суммаси тўлиқ киритилиши керак.');
            }

            try {
                $dateKey = \Carbon\CarbonImmutable::parse($dateRaw)->format('Y-m-d');
            } catch (\Throwable $e) {
                return back()
                    ->withInput()
                    ->with('error', 'График санаси нотўғри форматда.');
            }

            $amount = $this->toNumericValue($amountRaw);
            if ($amount < 0) {
                return back()
                    ->withInput()
                    ->with('error', 'График суммаси манфий бўлиши мумкин эмас.');
            }

            if ($amount == 0.0) {
                continue;
            }

            $normalizedSchedule[$dateKey] = (float) ($normalizedSchedule[$dateKey] ?? 0.0) + $amount;
        }

        ksort($normalizedSchedule);

        $schedulePayload = empty($normalizedSchedule)
            ? null
            : json_encode($normalizedSchedule, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('apz_contracts')
            ->where('contract_id', $contractId)
            ->update(['payment_schedule' => $schedulePayload]);

        $routeParams = ['contractId' => $contractId];

        $page = max(1, (int) $request->input('page', 1));
        if ($page > 1) {
            $routeParams['page'] = $page;
        }

        $back = trim((string) $request->input('back', ''));
        if ($back !== '') {
            $routeParams['back'] = $back;
        }

        return redirect()
            ->route('contracts.show', $routeParams)
            ->with('success', 'Тўлов жадвали янгиланди.');
    }

    private function canEditContractSchedule($user): bool
    {
        if (!$user) {
            return false;
        }

        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        $name = mb_strtolower(trim((string) ($user->name ?? '')));

        return $email === 'superadmin@example.com' && $name === 'administrator';
    }

    // ──────────────────────────────────────────────────────────────────────
    // MODAL: AJAX — payments list (district filter, month filter, contract filter)
    // ──────────────────────────────────────────────────────────────────────
    public function modalPayments(Request $request)
    {
        $district    = $request->district;
        $month       = $request->month;
        $year        = $request->year ? (int) $request->year : null;
        $contractId  = $request->contract_id ? (int) $request->contract_id : null;
        $page        = max(1, (int) $request->get('page', 1));
        $perPage     = 20;
        $offset      = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($district)   { $where[] = 'p.district = ?';    $params[] = $district; }
        if ($month)      { $where[] = 'p.month = ?';       $params[] = $month; }
        if ($year)       { $where[] = 'p.year = ?';        $params[] = $year; }
        if ($contractId) { $where[] = 'p.contract_id = ?'; $params[] = $contractId; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = DB::selectOne(
            "SELECT COUNT(*) as cnt FROM apz_payments p {$whereSQL}", $params
        )->cnt;

        $rows = DB::select(
            "SELECT p.id, p.payment_date, p.contract_id, p.district, p.type, p.flow,
                    p.amount, p.year, p.month, p.company_name,
                    p.payment_purpose, c.investor_name, c.contract_number
             FROM apz_payments p
             LEFT JOIN apz_contracts c ON c.contract_id = p.contract_id
             {$whereSQL}
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return response()->json([
            'rows'       => $rows,
            'total'      => (int) $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'last_page'  => (int) ceil($total / $perPage),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // MODAL: AJAX — single contract detail (schedule + payments)
    // ──────────────────────────────────────────────────────────────────────
    public function modalContract(Request $request, $contractId)
    {
        $payload = $this->getContractDetailPayload((int) $contractId, max(1, (int) $request->get('page', 1)), 15);
        if (!$payload) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'contract' => $payload['contract'],
            'schedule' => $payload['schedule'],
            'payments' => $payload['payments'],
            'total'    => (int) $payload['total'],
            'page'     => $payload['page'],
            'per_page' => $payload['per_page'],
            'last_page'=> $payload['last_page'],
        ]);
    }

    private function getContractDetailPayload(int $contractId, int $page = 1, int $perPage = 15): ?array
    {
        $contract = DB::selectOne(
            "SELECT c.*,
                    COALESCE(agg.total_paid, 0)     as total_paid,
                    COALESCE(agg.payment_count, 0)  as payment_count
             FROM apz_contracts c
             LEFT JOIN (
                 SELECT contract_id,
                        SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) as total_paid,
                        COUNT(*) as payment_count
                 FROM apz_payments
                 WHERE contract_id = ?
                 GROUP BY contract_id
             ) agg ON agg.contract_id = c.contract_id
             WHERE c.contract_id = ?
             LIMIT 1",
            [$contractId, $contractId]
        );

        if (!$contract) {
            return null;
        }

        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = (int) (DB::selectOne(
            'SELECT COUNT(*) as cnt FROM apz_payments WHERE contract_id = ?', [$contractId]
        )->cnt ?? 0);

        $payments = DB::select(
            "SELECT id, payment_date, type, flow, amount, month, year, company_name, payment_purpose
             FROM apz_payments WHERE contract_id = ?
             ORDER BY payment_date DESC, id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            [$contractId]
        );

        $schedule = [];
        if ($contract->payment_schedule) {
            $sched = json_decode($contract->payment_schedule, true);
            if (is_array($sched)) {
                foreach ($sched as $date => $amt) {
                    try {
                        $dt = \Carbon\Carbon::parse($date);
                        $schedule[] = [
                            'date' => $dt->format('d.m.Y'),
                            'amount' => round((float) $amt, 4),
                            'sort_key' => $dt->format('Y-m-d'),
                        ];
                    } catch (\Exception $e) {}
                }
            }
        }

        usort($schedule, fn($a, $b) => strcmp($a['sort_key'], $b['sort_key']));
        $schedule = array_map(function ($row) {
            unset($row['sort_key']);
            return $row;
        }, $schedule);

        return [
            'contract'  => $contract,
            'schedule'  => $schedule,
            'payments'  => $payments,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    private function formatNumber($value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, '.', ' ');
    }

    private function formatDate(?string $date): string
    {
        if (!$date) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d.m.Y');
        } catch (\Throwable $e) {
            return '—';
        }
    }

    private function statusClass(string $statusKey, string $inProgressClass = 'status-inprogress'): string
    {
        return match ($statusKey) {
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            default => $inProgressClass,
        };
    }

    private function issueClass(string $issueKey): string
    {
        return match ($issueKey) {
            'problem' => 'issue-problem',
            'no_problem' => 'issue-ok',
            default => 'issue-unknown',
        };
    }

    private function completionBandClass(?float $pct): string
    {
        return match (true) {
            $pct === null => 'diff-muted',
            $pct < 10 => 'diff-red',
            $pct < 35 => 'diff-orange',
            $pct < 60 => 'diff-yellow',
            default => 'diff-green',
        };
    }

    private function buildStatusOptions(string $selected): array
    {
        $items = [
            ['value' => 'all', 'label' => 'Барчаси'],
            ['value' => 'in_progress', 'label' => 'Амалдаги'],
            ['value' => 'completed', 'label' => 'Якунланган'],
            ['value' => 'cancelled', 'label' => 'Бекор қилинган'],
        ];

        foreach ($items as &$item) {
            $item['selected'] = $item['value'] === $selected;
        }

        return $items;
    }

    private function buildIssueOptions(string $selected, string $allLabel = 'Муаммо: барчаси'): array
    {
        $items = [
            ['value' => 'all', 'label' => $allLabel],
            ['value' => 'problem', 'label' => 'Муаммоли'],
            ['value' => 'no_problem', 'label' => 'Муаммосиз'],
            ['value' => 'unknown', 'label' => 'Кўрсатилмаган'],
        ];

        foreach ($items as &$item) {
            $item['selected'] = $item['value'] === $selected;
        }

        return $items;
    }

    private function buildDebtorOptions(bool $onlyDebtors): array
    {
        return [
            ['value' => '0', 'label' => 'Қарз: барчаси', 'selected' => !$onlyDebtors],
            ['value' => '1', 'label' => 'Фақат қарздорлар', 'selected' => $onlyDebtors],
        ];
    }

    private function normalizeDebtTypeFilter(?string $debtType): string
    {
        $debtType = strtolower(trim((string) $debtType));
        if ($debtType === '') {
            return 'all';
        }

        return in_array($debtType, ['all', 'overdue', 'unoverdue'], true)
            ? $debtType
            : 'all';
    }

    private function buildDebtTypeOptions(string $selected): array
    {
        $items = [
            ['value' => 'all', 'label' => 'Қарз тури: барчаси'],
            ['value' => 'overdue', 'label' => 'Муддати ўтган қарздорлик'],
            ['value' => 'unoverdue', 'label' => 'Муддати ўтмаган қарздорлик'],
        ];

        foreach ($items as &$item) {
            $item['selected'] = $item['value'] === $selected;
        }

        return $items;
    }

    private function buildDistrictOptions(array $districts, ?string $selected, string $allLabel = 'Туман: барчаси'): array
    {
        $items = [
            ['value' => '', 'label' => $allLabel, 'selected' => empty($selected)],
        ];

        foreach ($districts as $district) {
            $districtName = (string) $district;
            $items[] = [
                'value' => $districtName,
                'label' => $districtName,
                'selected' => $selected === $districtName,
            ];
        }

        return $items;
    }

    private function buildPagination(string $routeName, array $query, int $page, int $lastPage): array
    {
        $query = array_filter($query, static fn($value) => !($value === null || $value === ''));

        $pages = [];
        for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++) {
            $pages[] = [
                'number' => $p,
                'url' => route($routeName, array_merge($query, ['page' => $p])),
                'active' => $p === $page,
            ];
        }

        return [
            'first_url' => $page > 1 ? route($routeName, array_merge($query, ['page' => 1])) : null,
            'prev_url' => $page > 1 ? route($routeName, array_merge($query, ['page' => $page - 1])) : null,
            'next_url' => $page < $lastPage ? route($routeName, array_merge($query, ['page' => $page + 1])) : null,
            'last_url' => $page < $lastPage ? route($routeName, array_merge($query, ['page' => $lastPage])) : null,
            'pages' => $pages,
        ];
    }

    private function buildContractPagination(int $contractId, ?string $backUrl, int $page, int $lastPage): array
    {
        $query = array_filter([
            'back' => $backUrl,
        ], static fn($value) => !($value === null || $value === ''));

        $routeParams = fn (int $targetPage) => array_merge(['contractId' => $contractId, 'page' => $targetPage], $query);

        $pages = [];
        for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++) {
            $pages[] = [
                'number' => $p,
                'url' => route('contracts.show', $routeParams($p)),
                'active' => $p === $page,
            ];
        }

        return [
            'first_url' => $page > 1 ? route('contracts.show', $routeParams(1)) : null,
            'prev_url' => $page > 1 ? route('contracts.show', $routeParams($page - 1)) : null,
            'next_url' => $page < $lastPage ? route('contracts.show', $routeParams($page + 1)) : null,
            'last_url' => $page < $lastPage ? route('contracts.show', $routeParams($lastPage)) : null,
            'pages' => $pages,
        ];
    }

    private function mapSummaryContractsForView(array $contracts, int $page, int $perPage, string $backUrl): array
    {
        $items = [];

        foreach ($contracts as $index => $contract) {
            $plan = (float) ($contract->contract_value ?? 0);
            $fact = (float) ($contract->total_paid ?? 0);
            $balance = $plan - $fact;
            $pct = $plan > 0 ? round($fact / $plan * 100, 1) : 0;

            $statusKey = (string) ($contract->status_key ?? 'in_progress');
            $issueKey = (string) ($contract->issue_key ?? 'unknown');

            $items[] = [
                'row_num' => ($page - 1) * $perPage + $index + 1,
                'investor_name' => Str::limit((string) ($contract->investor_name ?: '—'), 40),
                'district' => $contract->district ?: '—',
                'contract_number' => $contract->contract_number ?: '—',
                'contract_date' => $this->formatDate($contract->contract_date ?? null),
                'status_label' => $contract->status_label ?? 'Амалдаги',
                'status_class' => $this->statusClass($statusKey, 'status-active'),
                'issue_label' => $contract->issue_label ?? '—',
                'issue_class' => $this->issueClass($issueKey),
                'plan_mln' => $plan > 0 ? $this->formatNumber($plan, 2) : '—',
                'payment_terms' => $contract->payment_terms ?: '—',
                'installments_count' => $contract->installments_count ?: '—',
                'fact_mln' => $fact > 0 ? $this->formatNumber($fact, 2) : '—',
                'balance_mln' => $plan > 0 ? $this->formatNumber($balance, 2) : '—',
                'balance_class' => $balance <= 0 ? 'txt-good' : 'txt-danger',
                'progress_show' => $plan > 0,
                'progress_width' => min((int) round($pct), 100),
                'progress_class' => $pct >= 100 ? 'over' : '',
                'progress_label' => $this->formatNumber($pct, 1) . '%',
                'detail_url' => route('contracts.show', ['contractId' => $contract->contract_id, 'back' => $backUrl]),
            ];
        }

        return $items;
    }

    private function mapDebtContractsForView(array $contracts, int $page, int $perPage, string $backUrl): array
    {
        $items = [];

        foreach ($contracts as $index => $contract) {
            $plan = (float) ($contract->contract_value ?? 0);
            $fact = (float) ($contract->total_paid ?? 0);
            $debt = (float) ($contract->debt ?? 0);
            $overdueDebt = (float) ($contract->overdue_debt ?? $debt);
            $unoverdueDebt = (float) ($contract->unoverdue_debt ?? max(($plan - $fact) - $overdueDebt, 0.0));
            $diff = (float) ($contract->plan_fact_diff ?? 0);
            $pct = $plan > 0 ? round(($fact / $plan) * 100, 1) : null;

            $statusKey = (string) ($contract->status_key ?? 'in_progress');
            $issueKey = (string) ($contract->issue_key ?? 'unknown');

            $items[] = [
                'row_num' => ($page - 1) * $perPage + $index + 1,
                'investor_name' => $contract->investor_name ?: '—',
                'district' => $contract->district ?: '—',
                'contract_number' => $contract->contract_number ?: '—',
                'contract_date' => $this->formatDate($contract->contract_date ?? null),
                'status_label' => $contract->status_label ?? 'Амалдаги',
                'status_class' => $this->statusClass($statusKey),
                'issue_label' => $contract->issue_label ?? '—',
                'issue_class' => $this->issueClass($issueKey),
                'plan_mln' => $plan > 0 ? $this->formatNumber($plan, 2) : '—',
                'fact_mln' => $fact > 0 ? $this->formatNumber($fact, 2) : '—',
                'debt_mln' => $overdueDebt > 0 ? $this->formatNumber($overdueDebt, 2) : '0.00',
                'unoverdue_debt_mln' => $unoverdueDebt > 0 ? $this->formatNumber($unoverdueDebt, 2) : '0.00',
                'diff_mln' => $this->formatNumber($diff, 2),
                'diff_class' => $this->completionBandClass($pct),
                'detail_url' => route('contracts.show', ['contractId' => $contract->contract_id, 'back' => $backUrl]),
            ];
        }

        return $items;
    }

    private function mapContractPaymentsForView(array $payments, int $page, int $perPage): array
    {
        $rows = [];

        foreach ($payments as $index => $payment) {
            $isIn = (($payment->flow ?? '') === 'Приход');
            $rows[] = [
                'row_num' => ($page - 1) * $perPage + $index + 1,
                'payment_date' => $payment->payment_date ?: '—',
                'type' => $payment->type ?: '—',
                'flow' => $payment->flow ?: '—',
                'flow_class' => $isIn ? 'flow-in' : 'flow-out',
                'amount_signed_mln' => ($isIn ? '+' : '-') . $this->formatNumber(((float) ($payment->amount ?? 0)), 4),
                'purpose' => $payment->payment_purpose ?: '—',
            ];
        }

        return $rows;
    }

    private function isXlsxExportRequest(Request $request): bool
    {
        $export = strtolower(trim((string) $request->get('export', '')));
        return in_array($export, ['1', 'xlsx', 'xls', 'excel'], true);
    }

    private function exportUnifiedSystemXlsx(Request $request, string $whereSQL, array $params)
    {
        $grafikHeaders = $this->buildGrafikScheduleHeaders();

        $rows = DB::select(
            "SELECT p.id, p.payment_date, p.contract_id, p.inn,
                    p.debit_amount, p.credit_amount, p.payment_purpose,
                    p.flow, p.month, p.amount, p.district, p.type, p.year,
                    p.company_name, c.investor_name,
                    c.contract_status, c.payment_schedule
             FROM apz_payments p
             LEFT JOIN apz_contracts c ON c.contract_id = p.contract_id
             {$whereSQL}
             ORDER BY p.id DESC",
            $params
        );

        $tenants = [];

        foreach ($rows as $row) {
            $tenantInn = trim((string) ($row->inn ?? ''));
            $tenantCompany = trim((string) ($row->company_name ?? ''));
            $tenantInvestor = trim((string) ($row->investor_name ?? ''));
            $tenantDistrict = trim((string) ($row->district ?? ''));

            $tenantKey = $tenantDistrict . '|' . $tenantInn . '|' . $tenantCompany . '|' . $tenantInvestor;
            if ($tenantKey === '|||') {
                $tenantKey = 'district:' . $tenantDistrict . '|contract:' . (string) ($row->contract_id ?? '') . '|payment:' . (string) ($row->id ?? '');
            }

            if (!isset($tenants[$tenantKey])) {
                $tenants[$tenantKey] = [
                    'payment_date' => $this->formatDate($row->payment_date ?? null),
                    'contract_id' => (int) ($row->contract_id ?? 0),
                    'inn' => $tenantInn,
                    'debit_amount' => 0.0,
                    'credit_amount' => 0.0,
                    'payment_purpose' => (string) ($row->payment_purpose ?? ''),
                    'flow' => (string) ($row->flow ?? ''),
                    'month' => (string) ($row->month ?? ''),
                    'amount' => 0.0,
                    'district' => (string) ($row->district ?? ''),
                    'type' => (string) ($row->type ?? ''),
                    'year' => (string) ($row->year ?? ''),
                    'company_name' => $tenantCompany !== '' ? $tenantCompany : $tenantInvestor,
                    'schedule_total' => 0.0,
                    'fact_payment_total' => 0.0,
                    'schedule_by_date' => array_fill_keys($grafikHeaders, 0.0),
                    'fact_by_date' => [],
                    'status_labels' => [],
                    'seen_contracts' => [],
                ];
            }

            $tenants[$tenantKey]['debit_amount'] += (float) ($row->debit_amount ?? 0);
            $tenants[$tenantKey]['credit_amount'] += (float) ($row->credit_amount ?? 0);
            $tenants[$tenantKey]['amount'] += (float) ($row->amount ?? 0);

            if (($row->flow ?? '') === 'Приход') {
                $tenants[$tenantKey]['fact_payment_total'] += (float) ($row->amount ?? 0);
                $factDate = $this->formatDateToGrafikHeader($row->payment_date ?? null);
                if ($factDate !== null) {
                    $tenants[$tenantKey]['fact_by_date'][$factDate] =
                        (float) ($tenants[$tenantKey]['fact_by_date'][$factDate] ?? 0)
                        + (float) ($row->amount ?? 0);
                }
            }

            $contractId = (int) ($row->contract_id ?? 0);
            if ($contractId > 0 && !isset($tenants[$tenantKey]['seen_contracts'][$contractId])) {
                $tenants[$tenantKey]['seen_contracts'][$contractId] = true;

                $scheduleByDate = $this->parseScheduleByDate($row->payment_schedule ?? null);
                foreach ($scheduleByDate as $scheduleDate => $scheduleAmount) {
                    if (!array_key_exists($scheduleDate, $tenants[$tenantKey]['schedule_by_date'])) {
                        continue;
                    }

                    $tenants[$tenantKey]['schedule_by_date'][$scheduleDate] += $scheduleAmount;
                    $tenants[$tenantKey]['schedule_total'] += $scheduleAmount;
                }

                $statusLabel = $this->contractStatusLabel($row->contract_status ?? null);
                if ($statusLabel !== '') {
                    $tenants[$tenantKey]['status_labels'][$statusLabel] = true;
                }
            }

            if ($tenants[$tenantKey]['payment_purpose'] === '' && !empty($row->payment_purpose)) {
                $tenants[$tenantKey]['payment_purpose'] = (string) $row->payment_purpose;
            }
        }

        $sheetRows = [[
            'Дата',
            'ID',
            'ИНН',
            ' Сумма дебет ',
            ' Сумма кредит ',
            'Назначение платежа',
            'Поток',
            'Месяц',
            ' Cумма ',
            'Район',
            'Тип',
            'ГОД',
            'Корхона номи',
            'Reja-jadval (план)',
            'Факт тўлов (Приход)',
            'Шартнома ҳолати',
            'График (детал)',
            'Факт тўловлар (детал)',
            ...$grafikHeaders,
        ]];

        $tenantRows = array_values($tenants);
        usort($tenantRows, static function (array $left, array $right): int {
            $districtCmp = strcmp((string) ($left['district'] ?? ''), (string) ($right['district'] ?? ''));
            if ($districtCmp !== 0) {
                return $districtCmp;
            }

            $companyCmp = strcmp((string) ($left['company_name'] ?? ''), (string) ($right['company_name'] ?? ''));
            if ($companyCmp !== 0) {
                return $companyCmp;
            }

            return strcmp((string) ($left['inn'] ?? ''), (string) ($right['inn'] ?? ''));
        });

        foreach ($tenantRows as $tenant) {
            $statusLabels = isset($tenant['status_labels']) && is_array($tenant['status_labels'])
                ? implode(', ', array_keys($tenant['status_labels']))
                : '';

            $scheduleByDate = (isset($tenant['schedule_by_date']) && is_array($tenant['schedule_by_date']))
                ? $tenant['schedule_by_date']
                : [];

            $factByDate = (isset($tenant['fact_by_date']) && is_array($tenant['fact_by_date']))
                ? $tenant['fact_by_date']
                : [];

            $grafikCells = [];
            foreach ($grafikHeaders as $headerDate) {
                $grafikCells[] = round((float) ($scheduleByDate[$headerDate] ?? 0), 2);
            }

            $sheetRows[] = array_merge([
                (string) ($tenant['payment_date'] ?? ''),
                (int) ($tenant['contract_id'] ?? 0),
                (string) ($tenant['inn'] ?? ''),
                round((float) ($tenant['debit_amount'] ?? 0), 2),
                round((float) ($tenant['credit_amount'] ?? 0), 2),
                (string) ($tenant['payment_purpose'] ?? ''),
                (string) ($tenant['flow'] ?? ''),
                (string) ($tenant['month'] ?? ''),
                round((float) ($tenant['amount'] ?? 0), 2),
                (string) ($tenant['district'] ?? ''),
                (string) ($tenant['type'] ?? ''),
                (string) ($tenant['year'] ?? ''),
                (string) ($tenant['company_name'] ?? ''),
                round((float) ($tenant['schedule_total'] ?? 0), 2),
                round((float) ($tenant['fact_payment_total'] ?? 0), 2),
                $statusLabels !== '' ? $statusLabels : '—',
                $this->formatAmountsByDate($scheduleByDate),
                $this->formatAmountsByDate($factByDate),
            ], $grafikCells);
        }

        $fileName = 'apz_tenants_distinct_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => 'Tenants', 'rows' => $sheetRows],
        ]);
    }

    private function fetchContractFinancialRows(array $whereConditions = []): array
    {
        $cleanConditions = array_values(array_filter($whereConditions, static fn ($condition) => is_string($condition) && trim($condition) !== ''));
        $whereSql = $cleanConditions ? ('WHERE ' . implode(' AND ', $cleanConditions)) : '';

        return DB::select("
            SELECT c.id, c.contract_id, c.investor_name, c.district,
                   c.contract_number, c.contract_date, c.phone,
                   c.contract_status, c.construction_issues,
                   c.contract_value, c.payment_schedule, c.payment_terms,
                   COALESCE(paid.total_paid, 0) as total_paid
            FROM apz_contracts c
            LEFT JOIN (
                SELECT contract_id,
                       SUM(amount) as total_paid
                FROM apz_payments
                WHERE flow = 'Приход'
                GROUP BY contract_id
            ) paid ON paid.contract_id = c.contract_id
            {$whereSql}
            ORDER BY c.id
        ");
    }

    private function buildOverdueDebtStatsFromContracts(array $contracts): object
    {
        $debtorsCount = 0;
        $debtTotal = 0.0;

        foreach ($contracts as $contract) {
            $metrics = $this->calculateContractFinancials(
                $contract->contract_value ?? 0,
                $contract->total_paid ?? 0,
                $contract->payment_schedule ?? null,
                $contract->payment_terms ?? null,
                $contract->contract_date ?? null
            );

            $overdueDebt = (float) ($metrics['overdue_debt'] ?? 0.0);

            if ($overdueDebt > 0.0) {
                $debtorsCount++;
                $debtTotal += $overdueDebt;
            }
        }

        return (object) [
            'debtors_count' => $debtorsCount,
            'debt_total' => $debtTotal,
        ];
    }

    private function calculateContractFinancials($contractValue, $paidAmount, $scheduleRaw = null, $paymentTerms = null, $contractDate = null): array
    {
        $plan = max((float) $contractValue, 0.0);
        $fact = max((float) $paidAmount, 0.0);
        $planDueToday = $this->resolvePlanAmountByToday($plan, $scheduleRaw, $paymentTerms, $contractDate);
        $overdueDebt = max($planDueToday - $fact, 0.0);
        $totalDebt = max($plan - $fact, 0.0);
        $unoverdueDebt = max($totalDebt - $overdueDebt, 0.0);
        $diff = $plan - $fact;

        return [
            'plan' => $plan,
            'fact' => $fact,
            'plan_due_today' => $planDueToday,
            'debt' => $overdueDebt,
            'overdue_debt' => $overdueDebt,
            'unoverdue_debt' => $unoverdueDebt,
            'total_debt' => $totalDebt,
            'diff' => $diff,
            'pct' => $plan > 0 ? round(($fact / $plan) * 100, 1) : 0.0,
        ];
    }

    private function resolvePlanAmountByToday(float $contractValue, $scheduleRaw, $paymentTerms = null, $contractDate = null): float
    {
        $today = \Carbon\CarbonImmutable::today();
        $initialDue = $this->resolveInitialPaymentDueAmount($contractValue, $paymentTerms, $contractDate, $today);

        $scheduleByDate = $this->parseScheduleByDate($scheduleRaw);
        if (empty($scheduleByDate)) {
            return max($contractValue, $initialDue);
        }

        $planDueToday = 0.0;
        $latestDueDate = null;

        foreach ($scheduleByDate as $date => $amount) {
            $normalizedDate = trim((string) $date);
            if ($normalizedDate === '') {
                continue;
            }

            try {
                $dueDate = \Carbon\CarbonImmutable::createFromFormat('d.m.Y', $normalizedDate)->endOfDay();
            } catch (\Throwable $e) {
                try {
                    $dueDate = \Carbon\CarbonImmutable::parse($normalizedDate)->endOfDay();
                } catch (\Throwable $inner) {
                    continue;
                }
            }

            if ($latestDueDate === null || $dueDate->greaterThan($latestDueDate)) {
                $latestDueDate = $dueDate;
            }

            if ($dueDate->lessThan($today)) {
                $planDueToday += (float) $amount;
            }
        }

        if ($latestDueDate === null) {
            $planDueToday = max($contractValue, $planDueToday);
        } elseif ($latestDueDate->lessThan($today) && $contractValue > $planDueToday) {
            $planDueToday = $contractValue;
        }

        if ($initialDue > $planDueToday) {
            $planDueToday = $initialDue;
        }

        if ($planDueToday <= 0.0) {
            return 0.0;
        }

        return $contractValue > 0 ? min($planDueToday, $contractValue) : $planDueToday;
    }

    private function resolveInitialPaymentDueAmount(float $contractValue, $paymentTerms, $contractDate, \Carbon\CarbonImmutable $today): float
    {
        if ($contractValue <= 0.0) {
            return 0.0;
        }

        $initialPercent = $this->extractInitialPaymentPercent($paymentTerms);
        if ($initialPercent <= 0.0) {
            return 0.0;
        }

        if (!$this->isContractDateReached($contractDate, $today)) {
            return 0.0;
        }

        return ($contractValue * $initialPercent) / 100;
    }

    private function extractInitialPaymentPercent($paymentTerms): float
    {
        $raw = trim((string) $paymentTerms);
        if ($raw === '') {
            return 0.0;
        }

        $raw = str_replace('%', '', $raw);
        $raw = preg_replace('/\s+/u', '', $raw);

        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)\/(?:[0-9]+(?:[\.,][0-9]+)?)$/u', $raw, $m)) {
            $firstPart = (float) str_replace(',', '.', $m[1]);
            return max(0.0, min($firstPart, 100.0));
        }

        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)$/u', $raw, $m)) {
            $fullPercent = (float) str_replace(',', '.', $m[1]);
            return max(0.0, min($fullPercent, 100.0));
        }

        return 0.0;
    }

    private function isContractDateReached($contractDate, \Carbon\CarbonImmutable $today): bool
    {
        $raw = trim((string) $contractDate);
        if ($raw === '') {
            return true;
        }

        try {
            $contractDateValue = \Carbon\CarbonImmutable::parse($raw)->endOfDay();
        } catch (\Throwable $e) {
            return true;
        }

        return $contractDateValue->lessThan($today);
    }

    private function buildGrafikScheduleHeaders(): array
    {
        $headers = [];
        $cursor = \Carbon\CarbonImmutable::create(2024, 4, 1)->startOfMonth();
        $end = \Carbon\CarbonImmutable::create(2030, 12, 1)->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $headers[] = $cursor->endOfMonth()->format('d.m.Y');
            $cursor = $cursor->addMonth()->startOfMonth();
        }

        return $headers;
    }

    private function parseScheduleByDate($scheduleRaw): array
    {
        if (is_string($scheduleRaw)) {
            $decoded = json_decode($scheduleRaw, true);
            if (is_array($decoded)) {
                $scheduleRaw = $decoded;
            }
        }

        if (!is_array($scheduleRaw) || empty($scheduleRaw)) {
            return [];
        }

        $result = [];

        foreach ($scheduleRaw as $dateKey => $amountRaw) {
            $normalizedDate = $this->normalizeGrafikDateKey((string) $dateKey);
            if ($normalizedDate === null) {
                continue;
            }

            $amount = $this->toNumericValue($amountRaw);
            if ($amount == 0.0) {
                continue;
            }

            $result[$normalizedDate] = (float) ($result[$normalizedDate] ?? 0) + $amount;
        }

        return $result;
    }

    private function normalizeGrafikDateKey(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['d.m.Y', 'j.n.Y', 'Y-m-d', 'n/j/Y', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                $date = \Carbon\CarbonImmutable::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->format('d.m.Y');
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return \Carbon\CarbonImmutable::parse($value)->format('d.m.Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatDateToGrafikHeader($value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return \Carbon\CarbonImmutable::parse($raw)->format('d.m.Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatAmountsByDate(array $amountsByDate): string
    {
        if (empty($amountsByDate)) {
            return '—';
        }

        $rows = [];
        foreach ($amountsByDate as $date => $amount) {
            $numeric = round((float) $amount, 2);
            if ($numeric == 0.0) {
                continue;
            }

            $rows[] = ['date' => $date, 'amount' => $numeric];
        }

        if (empty($rows)) {
            return '—';
        }

        usort($rows, static function (array $left, array $right): int {
            $leftTs = strtotime($left['date']);
            $rightTs = strtotime($right['date']);
            return $leftTs <=> $rightTs;
        });

        $parts = [];
        foreach ($rows as $row) {
            $parts[] = $row['date'] . ': ' . $this->formatNumber((float) $row['amount'], 2);
        }

        return implode(' | ', $parts);
    }

    private function toNumericValue($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-') {
            return 0.0;
        }

        $normalized = preg_replace('/[\s\x{00A0}\x{2007}]/u', '', $raw);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            if (preg_match('/,\d{1,4}$/', $normalized)) {
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function flattenScheduleForExport($scheduleRaw): string
    {
        if (!$scheduleRaw || !is_string($scheduleRaw)) {
            return '';
        }

        $decoded = json_decode($scheduleRaw, true);
        if (!is_array($decoded) || empty($decoded)) {
            return (string) $scheduleRaw;
        }

        $parts = [];
        foreach ($decoded as $date => $amount) {
            try {
                $formattedDate = \Carbon\Carbon::parse((string) $date)->format('d.m.Y');
            } catch (\Throwable $e) {
                $formattedDate = (string) $date;
            }

            $parts[] = $formattedDate . ': ' . $this->formatNumber(((float) $amount), 4);
        }

        return implode(' | ', $parts);
    }

    private function exportHomePaymentsXlsx(array $payments, array $filters)
    {
        $rows = [[
            'ID',
            'Сана',
            'Шартнома ID',
            'Туман',
            'Тур',
            'Оқим',
            'Сумма (сўм)',
            'Йил',
            'Ой',
            'Компания',
            'ИНН',
            'Тўлов мақсади',
            'Инвестор',
            'Шартнома рақами',
            'Шартнома ҳолати',
        ]];

        foreach ($payments as $payment) {
            $rows[] = [
                (int) ($payment->id ?? 0),
                $this->formatDate($payment->payment_date ?? null),
                (int) ($payment->contract_id ?? 0),
                (string) ($payment->district ?? ''),
                (string) ($payment->type ?? ''),
                (string) ($payment->flow ?? ''),
                (float) ($payment->amount ?? 0),
                (string) ($payment->year ?? ''),
                (string) ($payment->month ?? ''),
                (string) ($payment->company_name ?? ''),
                (string) ($payment->inn ?? ''),
                (string) ($payment->payment_purpose ?? ''),
                (string) ($payment->investor_name ?? ''),
                (string) ($payment->contract_number ?? ''),
                (string) ($payment->contract_status ?? ''),
            ];
        }

        $filterRows = [
            ['Фильтр', 'Қиймат'],
            ['Туман', $filters['district'] ?: 'Барчаси'],
            ['Йил', $filters['year'] ?: 'Барчаси'],
            ['Ой', $filters['month'] ?: 'Барчаси'],
            ['Тур', $filters['type'] ?: 'Барчаси'],
            ['Сана (дан)', $filters['date_from'] ?: '—'],
            ['Сана (гача)', $filters['date_to'] ?: '—'],
            ['Қидирув', $filters['search'] ?: '—'],
            ['Саралаш', ($filters['sort'] ?: 'id') . ' ' . strtoupper((string) ($filters['dir'] ?: 'desc'))],
            ['Экспорт санаси', now()->format('d.m.Y H:i')],
        ];

        $fileName = 'apz_tolovlar_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => 'Filters', 'rows' => $filterRows],
            ['name' => 'APZ_Tolovlar', 'rows' => $rows],
        ]);
    }

    private function exportSummary2Xlsx(array $contracts, ?string $district, string $status, string $issue, ?string $searchTerm)
    {
        $rows = [[
            'ID',
            'Компания',
            'Туман',
            'ИНН',
            'Шартнома рақами',
            'Шартнома санаси',
            'Ҳолат',
            'Қурилиш ҳолати',
            'Шартнома қиймати (сўм)',
            'Факт тўлов (сўм)',
            'Қолдиқ (сўм)',
            'Бажарилиш %',
            'Тўлов шарти',
            'Жадвал сони',
            'Тўловлар сони',
        ]];

        foreach ($contracts as $contract) {
            $plan = (float) ($contract->contract_value ?? 0);
            $fact = (float) ($contract->total_paid ?? 0);
            $balance = $plan - $fact;
            $pct = $plan > 0 ? round(($fact / $plan) * 100, 1) : 0;

            $rows[] = [
                (int) ($contract->contract_id ?? 0),
                (string) ($contract->investor_name ?? ''),
                (string) ($contract->district ?? ''),
                (string) ($contract->inn ?? ''),
                (string) ($contract->contract_number ?? ''),
                $this->formatDate($contract->contract_date ?? null),
                $contract->status_label ?? $this->contractStatusLabel($contract->contract_status ?? null),
                $contract->issue_label ?? $this->issueStatusLabel($contract->construction_issues ?? null),
                round($plan, 2),
                round($fact, 2),
                round($balance, 2),
                $pct,
                (string) ($contract->payment_terms ?? ''),
                (int) ($contract->installments_count ?? 0),
                (int) ($contract->payment_count ?? 0),
            ];
        }

        $filterRows = [
            ['Фильтр', 'Қиймат'],
            ['Туман', $district ?: 'Барчаси'],
            ['Ҳолат', $status],
            ['Муаммо', $issue],
            ['Қидирув', $searchTerm ?: '—'],
            ['Экспорт санаси', now()->format('d.m.Y H:i')],
        ];

        $fileName = 'summary2_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => 'Filters', 'rows' => $filterRows],
            ['name' => 'Summary2', 'rows' => $rows],
        ]);
    }

    private function exportDebtsXlsx(array $contracts, string $status, string $issue, ?string $district, ?string $searchTerm, string $debtType, bool $onlyDebtors)
    {
        $debtTypeLabel = match ($debtType) {
            'overdue' => 'Муддати ўтган қарздорлик',
            'unoverdue' => 'Муддати ўтмаган қарздорлик',
            default => 'Барчаси',
        };

        $reportTitle = $onlyDebtors
            ? 'Шартномалар бўйича қарздорлар ҳисоботи'
            : 'Шартномалар бўйича қарздорлик ҳисоботи';

        $tableHeader = [
            '№',
            'Компания номи',
            'Туман',
            'Шартнома рақами',
            'Шартнома санаси',
            'Ҳолат',
            'Қурилиш ҳолати',
            'Шартнома қиймати',
            'Факт тўлаган',
            'Муддати ўтган қарздорлик',
            'Муддати келмаган қарздорлик',
            'План-Факт фарқи',
        ];

        $rows = [
            [$reportTitle],
            ['Экспорт санаси: ' . now()->format('d.m.Y H:i')],
            ['Қарз тури: ' . $debtTypeLabel],
            [],
            $tableHeader,
        ];

        $totalContracts = 0;
        $sumPlan = 0.0;
        $sumFact = 0.0;
        $sumDebt = 0.0;
        $sumUnoverdueDebt = 0.0;
        $sumDiff = 0.0;

        $dataRows = [];

        foreach ($contracts as $index => $contract) {
            $metrics = $this->calculateContractFinancials(
                $contract->contract_value ?? 0,
                $contract->total_paid ?? 0,
                $contract->payment_schedule ?? null,
                $contract->payment_terms ?? null,
                $contract->contract_date ?? null
            );
            $plan = $metrics['plan'];
            $fact = $metrics['fact'];
            $debt = (float) ($contract->overdue_debt ?? $contract->debt ?? $metrics['overdue_debt'] ?? $metrics['debt']);
            $unoverdueDebt = (float) ($contract->unoverdue_debt ?? $metrics['unoverdue_debt'] ?? 0.0);
            $diff = (float) ($contract->plan_fact_diff ?? $metrics['diff']);

            $dataRows[] = [
                $index + 1,
                (string) ($contract->investor_name ?? ''),
                (string) ($contract->district ?? ''),
                (string) ($contract->contract_number ?? ''),
                $this->formatDate($contract->contract_date ?? null),
                $contract->status_label ?? $this->contractStatusLabel($contract->contract_status ?? null),
                $contract->issue_label ?? $this->issueStatusLabel($contract->construction_issues ?? null),
                $this->formatNumber($plan, 2),
                $this->formatNumber($fact, 2),
                $this->formatNumber($debt, 2),
                $this->formatNumber($unoverdueDebt, 2),
                $this->formatNumber($diff, 2),
            ];

            $totalContracts++;
            $sumPlan += (float) $plan;
            $sumFact += (float) $fact;
            $sumDebt += (float) $debt;
            $sumUnoverdueDebt += (float) $unoverdueDebt;
            $sumDiff += (float) $diff;
        }

        $rows[] = [
            '—',
            'ЖАМИ (' . $totalContracts . ' шартнома)',
            '',
            '',
            '',
            '',
            '',
            $this->formatNumber($sumPlan, 2),
            $this->formatNumber($sumFact, 2),
            $this->formatNumber($sumDebt, 2),
            $this->formatNumber($sumUnoverdueDebt, 2),
            $this->formatNumber($sumDiff, 2),
        ];

        foreach ($dataRows as $dataRow) {
            $rows[] = $dataRow;
        }

        $filterRows = [
            ['Фильтр', 'Қиймат'],
            ['Туман', $district ?: 'Барчаси'],
            ['Ҳолат', $status],
            ['Муаммо', $issue],
            ['Қарз тури', $debtTypeLabel],
            ['Қарздорлар', $onlyDebtors ? 'Фақат қарздорлар' : 'Барчаси'],
            ['Қидирув', $searchTerm ?: '—'],
            ['Экспорт санаси', now()->format('d.m.Y H:i')],
        ];

        $filePrefix = $onlyDebtors ? 'debtors' : 'debts';
        $sheetName = $onlyDebtors ? 'Debtors' : 'Debts';
        $fileName = $filePrefix . '_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => $sheetName, 'rows' => $rows],
            ['name' => 'Filters', 'rows' => $filterRows],
        ]);
    }

    private function exportDashboardXlsx(array $viewData)
    {
        $sheetRows = $this->buildDashboardStructuredContractPaymentRows($viewData);
        $fileName = 'dashboard_monitoring_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => 'Dashboard', 'rows' => $sheetRows],
        ]);
    }

    private function buildDashboardStructuredContractPaymentRows(array $viewData): array
    {
        $grafikHeaders = $this->buildGrafikScheduleHeaders();

        $contractHeaders = [
            'ID',
            'Туман',
            'МФЙ',
            'манзил тўлиқ',
            'Қурилиш ҳажми (метр.куп)',
            'Коэффицент',
            'Зона',
            'Рухсатнома',
            'АПЗ номер',
            'Кенгаш хулосаси',
            'Йиғма экспертиза хулосаси',
            'қурилишда муамолари ҳолати',
            'Объект тури',
            'БУЮРТМАЧИ ТУРИ',
            'Инвестор номи',
            'ИНН/ПИНФЛ',
            'телефон номер',
            'инвестор манзили',
            'шартнома номер',
            'шартнома санаси',
            'Шартнома ҳолати',
            'Шартнома қиймати',
            'Тўлов шарти',
            'reja-jadval',
        ];

        $paymentHeaders = [
            'Дата',
            'ID',
            'ИНН',
            ' Сумма дебет ',
            ' Сумма кредит ',
            'Назначение платежа',
            'Поток',
            'Месяц',
            ' Cумма ',
            'Район',
            'Тип',
            'ГОД',
            'Корхона номи',
        ];

        $sheetRows = [array_merge(['X', 'X позиция'], $contractHeaders, $grafikHeaders, $paymentHeaders)];

        $district = trim((string) ($viewData['selectedMonitoringDistrict'] ?? ''));
        $status = $this->normalizeRequestedStatus((string) ($viewData['selectedMonitoringStatus'] ?? 'all'));
        $issue = $this->normalizeRequestedIssue((string) ($viewData['selectedMonitoringIssue'] ?? 'all'));
        $search = trim((string) ($viewData['monitoringSearch'] ?? ''));

        $where = [];
        $params = [];

        if ($district !== '') {
            $where[] = 'c.district = ?';
            $params[] = $district;
        }

        $statusWhere = $this->buildContractStatusWhereSql($status, 'c');
        if ($statusWhere) {
            $where[] = $statusWhere;
        }

        $issueWhere = $this->buildConstructionIssueWhereSql($issue, 'c');
        if ($issueWhere) {
            $where[] = $issueWhere;
        }

        if ($search !== '') {
            $where[] = '(c.investor_name LIKE ? OR c.contract_number LIKE ? OR c.inn LIKE ? OR CAST(c.contract_id AS CHAR) LIKE ?)';
            $searchLike = '%' . $search . '%';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $rawContracts = DB::select(
            "SELECT c.id as row_id, c.contract_id, c.district, c.mfy, c.address, c.build_volume,
                    c.coefficient, c.zone, c.permit, c.apz_number,
                    c.council_decision, c.expertise, c.construction_issues,
                    c.object_type, c.client_type, c.investor_name, c.inn,
                    c.phone, c.investor_address, c.contract_number,
                    c.contract_date, c.contract_status, c.contract_value,
                    c.payment_terms, c.installments_count, c.payment_schedule
             FROM apz_contracts c
             {$whereSql}
             ORDER BY c.id",
            $params
        );

        if (empty($rawContracts)) {
            return $sheetRows;
        }

        $contracts = [];
        $seenContractKeys = [];

        foreach ($rawContracts as $rawContract) {
            $contractIdRaw = $rawContract->contract_id;
            $contractKey = ($contractIdRaw !== null && $contractIdRaw !== '')
                ? ('cid:' . (string) $contractIdRaw)
                : ('row:' . (string) ($rawContract->row_id ?? '0'));

            if (isset($seenContractKeys[$contractKey])) {
                continue;
            }

            $seenContractKeys[$contractKey] = true;
            $contracts[] = $rawContract;
        }

        $contractIds = [];
        $seenContractIds = [];
        foreach ($contracts as $contract) {
            $contractId = (int) ($contract->contract_id ?? 0);
            if ($contractId > 0 && !isset($seenContractIds[$contractId])) {
                $seenContractIds[$contractId] = true;
                $contractIds[] = $contractId;
            }
        }

        $paymentsByContract = [];
        if (!empty($contractIds)) {
            foreach (array_chunk($contractIds, 1000) as $contractIdsChunk) {
                $placeholders = implode(',', array_fill(0, count($contractIdsChunk), '?'));

                $payments = DB::select(
                    "SELECT p.id, p.payment_date, p.contract_id as payment_contract_id,
                            p.inn as payment_inn, p.debit_amount, p.credit_amount,
                            p.payment_purpose, p.flow, p.month, p.amount,
                            p.district as payment_district, p.type as payment_type,
                            p.year as payment_year, p.company_name
                     FROM apz_payments p
                     WHERE p.contract_id IN ({$placeholders})
                     ORDER BY p.contract_id, p.payment_date, p.id",
                    $contractIdsChunk
                );

                foreach ($payments as $payment) {
                    $paymentContractId = (int) ($payment->payment_contract_id ?? 0);
                    if (!isset($paymentsByContract[$paymentContractId])) {
                        $paymentsByContract[$paymentContractId] = [];
                    }
                    $paymentsByContract[$paymentContractId][] = $payment;
                }
            }
        }

        $xGroup = 0;

        foreach ($contracts as $contract) {
            $xGroup++;

            $scheduleByDate = $this->parseScheduleByDate($contract->payment_schedule ?? null);
            $scheduleCells = [];

            foreach ($grafikHeaders as $headerDate) {
                $amount = (float) ($scheduleByDate[$headerDate] ?? 0);
                $scheduleCells[] = $amount > 0 ? round($amount, 2) : '';
            }

            $contractDate = !empty($contract->contract_date)
                ? $this->formatDate((string) $contract->contract_date)
                : '';

            $contractCells = [
                (int) ($contract->contract_id ?? 0),
                (string) ($contract->district ?? ''),
                (string) ($contract->mfy ?? ''),
                (string) ($contract->address ?? ''),
                (float) ($contract->build_volume ?? 0),
                (string) ($contract->coefficient ?? ''),
                (string) ($contract->zone ?? ''),
                (string) ($contract->permit ?? ''),
                (string) ($contract->apz_number ?? ''),
                (string) ($contract->council_decision ?? ''),
                (string) ($contract->expertise ?? ''),
                (string) ($contract->construction_issues ?? ''),
                (string) ($contract->object_type ?? ''),
                (string) ($contract->client_type ?? ''),
                (string) ($contract->investor_name ?? ''),
                (string) ($contract->inn ?? ''),
                (string) ($contract->phone ?? ''),
                (string) ($contract->investor_address ?? ''),
                (string) ($contract->contract_number ?? ''),
                $contractDate,
                (string) ($contract->contract_status ?? ''),
                round((float) ($contract->contract_value ?? 0), 2),
                (string) ($contract->payment_terms ?? ''),
                (int) ($contract->installments_count ?? 0),
            ];

            $contractId = (int) ($contract->contract_id ?? 0);
            $contractPayments = $paymentsByContract[$contractId] ?? [];

            if (empty($contractPayments)) {
                $contractPayments = [null];
            }

            foreach ($contractPayments as $position => $payment) {
                $isFirstRow = $position === 0;

                $contractCellsForRow = $isFirstRow
                    ? $contractCells
                    : array_fill(0, count($contractHeaders), '');

                $scheduleCellsForRow = $isFirstRow
                    ? $scheduleCells
                    : array_fill(0, count($grafikHeaders), '');

                if ($payment === null) {
                    $paymentCells = array_fill(0, count($paymentHeaders), '');
                } else {
                    $paymentDate = !empty($payment->payment_date)
                        ? $this->formatDate((string) $payment->payment_date)
                        : '';

                    $paymentCells = [
                        $paymentDate,
                        $payment->payment_contract_id !== null ? (int) $payment->payment_contract_id : '',
                        (string) ($payment->payment_inn ?? ''),
                        $payment->debit_amount !== null ? round((float) $payment->debit_amount, 2) : '',
                        $payment->credit_amount !== null ? round((float) $payment->credit_amount, 2) : '',
                        (string) ($payment->payment_purpose ?? ''),
                        (string) ($payment->flow ?? ''),
                        (string) ($payment->month ?? ''),
                        $payment->amount !== null ? round((float) $payment->amount, 2) : '',
                        (string) ($payment->payment_district ?? ''),
                        (string) ($payment->payment_type ?? ''),
                        $payment->payment_year !== null ? (int) $payment->payment_year : '',
                        (string) ($payment->company_name ?? ''),
                    ];
                }

                $sheetRows[] = array_merge(
                    [$xGroup, $position + 1],
                    $contractCellsForRow,
                    $scheduleCellsForRow,
                    $paymentCells
                );
            }
        }

        return $sheetRows;
    }

    private function appendDashboardExportSection(array &$sheetRows, string $title, array $rows, int &$xPosition, string $vectorPrefix): void
    {
        $sheetRows[] = [$title];
        $sheetRows[] = ['X', 'X_Vector', '...'];

        foreach ($rows as $row) {
            $xPosition++;
            $xVector = $vectorPrefix . '.X' . str_pad((string) $xPosition, 5, '0', STR_PAD_LEFT);
            $values = is_array($row) ? array_values($row) : [(string) $row];
            $sheetRows[] = array_merge([$xPosition, $xVector], $values);
        }

        $sheetRows[] = [];
    }

    private function buildDashboardKpiRows(array $viewData): array
    {
        $global = $viewData['global'] ?? null;
        $contractStats = $viewData['contractStats'] ?? null;
        $debtorsStats = $viewData['debtorsStats'] ?? null;

        $totalIncome = (float) ($global->total_income ?? 0);
        $totalRecords = (int) ($global->total_records ?? 0);
        $dashboardDistrictCount = (int) ($viewData['dashboardDistrictCount'] ?? ($global->unique_districts ?? 0));
        $uniqueContracts = (int) ($global->unique_contracts ?? 0);

        $totalContracts = (int) ($contractStats->total ?? 0);
        $activeContracts = (int) ($contractStats->active ?? 0);
        $completedContracts = (int) ($contractStats->completed ?? 0);
        $cancelledContracts = (int) ($contractStats->cancelled ?? 0);
        $totalPlanValue = (float) ($contractStats->total_value ?? 0);

        $debtorsCount = (int) ($debtorsStats->debtors_count ?? 0);
        $debtorsTotal = (float) ($debtorsStats->debt_total ?? 0);
        $overallPct = $totalPlanValue > 0 ? round(($totalIncome / $totalPlanValue) * 100, 1) : 0.0;

        return [
            ['Кўрсаткич', 'Қиймат', 'Изоҳ'],
            ['Жами Приход (АПЗ), сўм', round($totalIncome, 1), number_format($totalRecords) . ' та ёзув'],
            ['Жами Шартномалар', $totalContracts, "Фаол: {$activeContracts} · Якун: {$completedContracts} · Бекор: {$cancelledContracts}"],
            ['Шартнома умумий қиймати, сўм', round($totalPlanValue, 1), 'режа-жадвал'],
            ['Туманлар сони', $dashboardDistrictCount, 'Уникал шартномалар: ' . number_format($uniqueContracts)],
            ['Қарздор шартномалар', $debtorsCount, number_format($debtorsTotal, 1, '.', ' ') . ' сўм'],
            ['Умумий бажарилиш, %', $overallPct, 'факт / режа'],
        ];
    }

    private function buildDashboardDrillDownRows(array $viewData): array
    {
        $summaryData = is_array($viewData['summaryData'] ?? null) ? $viewData['summaryData'] : [];
        $planFact = is_array($viewData['planFact'] ?? null) ? $viewData['planFact'] : [];
        $totals = is_array($viewData['totals'] ?? null) ? $viewData['totals'] : [];
        $dayRows = is_array($viewData['dayRows'] ?? null) ? $viewData['dayRows'] : [];
        $pfYear = is_array($viewData['pfYear'] ?? null) ? $viewData['pfYear'] : [];
        $pfMonth = is_array($viewData['pfMonth'] ?? null) ? $viewData['pfMonth'] : [];
        $pfYearPlan = is_array($viewData['pfYearPlan'] ?? null) ? $viewData['pfYearPlan'] : [];
        $pfMonthPlan = is_array($viewData['pfMonthPlan'] ?? null) ? $viewData['pfMonthPlan'] : [];

        $rows = [[
            'Даража',
            'Туман',
            'Йил',
            'Ой',
            'Кун',
            'Шартномалар',
            'Жами тушум (млн)',
            'АПЗ тўлови (млн)',
            'Пеня (млн)',
            'Қайтариш (млн)',
            'План (млн)',
            'Факт (млн)',
            'Қолдиқ (млн)',
            'Бажарилиш %',
        ]];

        $grandPlan = 0.0;
        $grandFact = 0.0;
        foreach ($planFact as $item) {
            if (!is_array($item)) {
                continue;
            }
            $grandPlan += (float) ($item['plan'] ?? 0);
            $grandFact += (float) ($item['fact'] ?? 0);
        }
        $grandBalance = $grandPlan - $grandFact;
        $grandPct = $grandPlan > 0 ? round(($grandFact / $grandPlan) * 100, 1) : 0;

        $rows[] = [
            'ЖАМИ',
            '',
            '',
            '',
            '',
            (int) ($totals['contract_count'] ?? 0),
            round((float) ($totals['total_income'] ?? 0), 2),
            round((float) ($totals['apz_payment'] ?? 0), 2),
            round((float) ($totals['penalty'] ?? 0), 2),
            round((float) ($totals['refund'] ?? 0), 2),
            round($grandPlan, 2),
            round($grandFact, 2),
            round($grandBalance, 2),
            $grandPct,
        ];

        foreach ($summaryData as $districtRow) {
            if (!is_array($districtRow)) {
                continue;
            }

            $district = (string) ($districtRow['district'] ?? '');
            $districtPlanFact = is_array($planFact[$district] ?? null)
                ? $planFact[$district]
                : ['plan' => 0, 'fact' => 0, 'balance' => 0, 'pct' => 0];

            $rows[] = [
                'ТУМАН',
                $district,
                '',
                '',
                '',
                (int) ($districtRow['contract_count'] ?? 0),
                round((float) ($districtRow['total_income'] ?? 0), 2),
                round((float) ($districtRow['apz_payment'] ?? 0), 2),
                round((float) ($districtRow['penalty'] ?? 0), 2),
                round((float) ($districtRow['refund'] ?? 0), 2),
                round((float) ($districtPlanFact['plan'] ?? 0), 2),
                round((float) ($districtPlanFact['fact'] ?? 0), 2),
                round((float) ($districtPlanFact['balance'] ?? 0), 2),
                (float) ($districtPlanFact['pct'] ?? 0),
            ];

            $districtYears = is_array($dayRows[$district] ?? null) ? array_keys($dayRows[$district]) : [];
            sort($districtYears);

            foreach ($districtYears as $year) {
                $yearRows = is_array($dayRows[$district][$year] ?? null) ? $dayRows[$district][$year] : [];
                $yearIncome = 0.0;
                $yearApz = 0.0;
                $yearPen = 0.0;
                $yearRef = 0.0;
                $yearContracts = 0;

                foreach ($yearRows as $monthRows) {
                    if (!is_array($monthRows)) {
                        continue;
                    }

                    foreach ($monthRows as $dayRow) {
                        if (!is_array($dayRow)) {
                            continue;
                        }
                        $yearIncome += (float) ($dayRow['income'] ?? 0);
                        $yearApz += (float) ($dayRow['apz'] ?? 0);
                        $yearPen += (float) ($dayRow['pen'] ?? 0);
                        $yearRef += (float) ($dayRow['ref'] ?? 0);
                        $yearContracts += (int) ($dayRow['cnt'] ?? 0);
                    }
                }

                $yearPlan = (float) ($pfYearPlan[$district][$year] ?? 0);
                $yearFact = (float) ($pfYear[$year][$district] ?? 0);
                $yearBalance = $yearPlan > 0 ? round($yearPlan - $yearFact, 2) : 0.0;
                $yearPct = $yearPlan > 0 ? round(($yearFact / $yearPlan) * 100, 1) : 0.0;

                $rows[] = [
                    'ЙИЛ',
                    $district,
                    (string) $year,
                    '',
                    '',
                    $yearContracts,
                    round($yearIncome, 2),
                    round($yearApz, 2),
                    round($yearPen, 2),
                    round($yearRef, 2),
                    round($yearPlan, 2),
                    round($yearFact, 2),
                    $yearPlan > 0 ? $yearBalance : 0,
                    $yearPlan > 0 ? $yearPct : 0,
                ];

                foreach ($yearRows as $month => $monthRows) {
                    if (!is_array($monthRows)) {
                        continue;
                    }

                    $monthIncome = 0.0;
                    $monthApz = 0.0;
                    $monthPen = 0.0;
                    $monthRef = 0.0;
                    $monthContracts = 0;

                    foreach ($monthRows as $dayRow) {
                        if (!is_array($dayRow)) {
                            continue;
                        }
                        $monthIncome += (float) ($dayRow['income'] ?? 0);
                        $monthApz += (float) ($dayRow['apz'] ?? 0);
                        $monthPen += (float) ($dayRow['pen'] ?? 0);
                        $monthRef += (float) ($dayRow['ref'] ?? 0);
                        $monthContracts += (int) ($dayRow['cnt'] ?? 0);
                    }

                    $monthPlan = (float) ($pfMonthPlan[$district][$year][$month] ?? 0);
                    $monthFact = (float) ($pfMonth[$year][$month][$district] ?? 0);
                    $monthBalance = $monthPlan > 0 ? round($monthPlan - $monthFact, 2) : 0.0;
                    $monthPct = $monthPlan > 0 ? round(($monthFact / $monthPlan) * 100, 1) : 0.0;

                    $rows[] = [
                        'ОЙ',
                        $district,
                        (string) $year,
                        (string) $month,
                        '',
                        $monthContracts,
                        round($monthIncome, 2),
                        round($monthApz, 2),
                        round($monthPen, 2),
                        round($monthRef, 2),
                        round($monthPlan, 2),
                        round($monthFact, 2),
                        $monthPlan > 0 ? $monthBalance : 0,
                        $monthPlan > 0 ? $monthPct : 0,
                    ];

                    foreach ($monthRows as $dayRow) {
                        if (!is_array($dayRow)) {
                            continue;
                        }

                        $rows[] = [
                            'КУН',
                            $district,
                            (string) $year,
                            (string) $month,
                            (string) ($dayRow['date_fmt'] ?? ''),
                            (int) ($dayRow['cnt'] ?? 0),
                            round((float) ($dayRow['income'] ?? 0), 2),
                            round((float) ($dayRow['apz'] ?? 0), 2),
                            round((float) ($dayRow['pen'] ?? 0), 2),
                            round((float) ($dayRow['ref'] ?? 0), 2),
                            round((float) ($dayRow['plan'] ?? 0), 2),
                            round((float) ($dayRow['fact'] ?? 0), 2),
                            round((float) ($dayRow['balance'] ?? 0), 2),
                            (float) ($dayRow['pct'] ?? 0),
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    // ──────────────────────────────────────────────────────────────────────
    // CACHE MANAGEMENT
    // ──────────────────────────────────────────────────────────────────────
    public function clearCache()
    {
        foreach (Cache::getStore() instanceof \Illuminate\Cache\ArrayStore
            ? []
            : ['apz_filters','apz_summary','apz_dashboard_data'] as $key) {
            Cache::forget($key);
        }
        // Clear all apz_ prefixed keys reliably
        $keys = [
            'apz_filters', 'apz_summary', 'apz_dashboard_data', 'apz_dashboard_data_v2',
            'apz_dashboard_data_v3_' . md5('all|all|all|'),
            'apz_dashboard_data_v4_' . md5('all|all|all|'),
            'apz_dashboard_data_v5_' . md5('all|all|all|'),
            'apz_dashboard_data_v6_' . md5('all|all|all|'),
            'apz_summary_report_all',
            'apz_summary2_' . md5('all|all|all|'),
            'apz_summary2_' . md5('all|in_progress|all|'),
            'apz_summary2_' . md5('all|completed|all|'),
            'apz_summary2_' . md5('all|cancelled|all|'),
            'apz_debts_' . md5('in_progress|all|all|'),
            'apz_debts_' . md5('all|all|all|'),
            'apz_debts_' . md5('completed|all|all|'),
            'apz_debts_' . md5('cancelled|all|all|'),
        ];
        foreach ($keys as $k) Cache::forget($k);
        // Also clear year-specific summary caches
        for ($y = 2024; $y <= 2030; $y++) {
            Cache::forget('apz_summary_report_' . $y);
        }

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Cache cleared']);
        }
        return redirect()->route('admin.dashboard')
            ->with('cache_cleared', 'Kesh muvaffaqiyatli tozalandi.');
    }

    public function warmCache()
    {
        $this->clearCache();
        $this->dashboard(request());
        $this->summary(request());
        $this->summary2(request());
        $this->debts(request());

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Cache warmed']);
        }
        return redirect()->route('admin.dashboard')
            ->with('cache_cleared', 'Kesh qayta qurildi.');
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPERS: Contract status & construction issue normalization
    // ──────────────────────────────────────────────────────────────────────
    private function normalizeRequestedStatus(?string $status, string $default = 'all'): string
    {
        $status = strtolower(trim((string) $status));
        if ($status === 'active') $status = 'in_progress';
        if ($status === '') $status = $default;

        return in_array($status, ['all', 'in_progress', 'completed', 'cancelled'], true)
            ? $status
            : $default;
    }

    private function buildContractStatusWhereSql(string $status, string $alias = 'c'): ?string
    {
        if ($status === 'all') return null;

        $statusExpr = "LOWER(TRIM(COALESCE({$alias}.contract_status, '')))";
        $cancelled  = "({$statusExpr} LIKE '%bekor%' OR {$statusExpr} LIKE '%бекор%' OR {$statusExpr} LIKE '%cancel%')";
        $completed  = "({$statusExpr} LIKE '%yakun%' OR {$statusExpr} LIKE '%якун%' OR {$statusExpr} LIKE '%tugal%' OR {$statusExpr} LIKE '%complete%')";

        return match ($status) {
            'cancelled'   => $cancelled,
            'completed'   => $completed,
            'in_progress' => "(NOT {$cancelled} AND NOT {$completed})",
            default       => null,
        };
    }

    private function normalizeContractStatus(?string $status): string
    {
        $status = mb_strtolower(trim((string) $status));
        if ($status === '') return 'in_progress';

        if (str_contains($status, 'bekor') || str_contains($status, 'бекор') || str_contains($status, 'cancel')) {
            return 'cancelled';
        }

        if (str_contains($status, 'yakun') || str_contains($status, 'якун') || str_contains($status, 'tugal') || str_contains($status, 'complete')) {
            return 'completed';
        }

        return 'in_progress';
    }

    private function contractStatusLabel(?string $status): string
    {
        return match ($this->normalizeContractStatus($status)) {
            'completed'   => 'Якунланган',
            'cancelled'   => 'Бекор қилинган',
            default       => 'Амалдаги',
        };
    }

    private function normalizeConstructionIssue(?string $issues): string
    {
        $issues = mb_strtolower(trim((string) $issues));
        if ($issues === '') return 'unknown';

        if (
            str_contains($issues, 'muammosiz') ||
            str_contains($issues, 'муаммосиз') ||
            str_contains($issues, 'no problem') ||
            str_contains($issues, 'без проблем')
        ) {
            return 'no_problem';
        }

        if (
            str_contains($issues, 'muammoli') ||
            str_contains($issues, 'муаммоли') ||
            str_contains($issues, 'problem') ||
            str_contains($issues, 'muammo') ||
            str_contains($issues, 'муаммо')
        ) {
            return 'problem';
        }

        return 'unknown';
    }

    private function normalizeRequestedIssue(?string $issue): string
    {
        $issue = strtolower(trim((string) $issue));
        if ($issue === '') $issue = 'all';

        return in_array($issue, ['all', 'problem', 'no_problem', 'unknown'], true)
            ? $issue
            : 'all';
    }

    private function issueStatusLabel(?string $issues): string
    {
        return match ($this->normalizeConstructionIssue($issues)) {
            'problem'    => 'Муаммоли',
            'no_problem' => 'Муаммосиз',
            default      => '—',
        };
    }

    private function buildConstructionIssueWhereSql(string $issue, string $alias = 'c'): ?string
    {
        $issueExpr  = "LOWER(TRIM(COALESCE({$alias}.construction_issues, '')))";
        $noProblem  = "({$issueExpr} LIKE '%muammosiz%' OR {$issueExpr} LIKE '%муаммосиз%' OR {$issueExpr} LIKE '%no problem%' OR {$issueExpr} LIKE '%без проблем%')";
        $problem    = "({$issueExpr} LIKE '%muammoli%' OR {$issueExpr} LIKE '%муаммоли%' OR {$issueExpr} LIKE '%muammo%' OR {$issueExpr} LIKE '%муаммо%' OR ({$issueExpr} LIKE '%problem%' AND {$issueExpr} NOT LIKE '%no problem%'))";

        return match ($issue) {
            'problem'    => $problem,
            'no_problem' => $noProblem,
            'unknown'    => "(NOT {$problem} AND NOT {$noProblem})",
            default      => null,
        };
    }

    private function ensureDashboardMonitoringLinks(array &$viewData, ?string $district, string $status, string $issue, ?string $search): void
    {
        $cleanQuery = static function (array $params): array {
            return array_filter($params, static fn ($value) => !($value === null || $value === ''));
        };

        $summaryBase = [
            'district' => $district,
            'status' => $status !== 'all' ? $status : null,
            'issue' => $issue !== 'all' ? $issue : null,
            'search' => $search,
        ];

        $debtsBase = [
            'district' => $district,
            'status' => $status,
            'issue' => $issue !== 'all' ? $issue : null,
            'search' => $search,
            'debt_type' => 'overdue',
            'debtors' => null,
        ];

        if (isset($viewData['monitoringSummaryRows']) && is_array($viewData['monitoringSummaryRows'])) {
            foreach ($viewData['monitoringSummaryRows'] as &$row) {
                if (!is_array($row) || !empty($row['list_url'])) {
                    continue;
                }

                $label = (string) ($row['label'] ?? '');

                $row['list_url'] = match ($label) {
                    'Муаммоли' => route('summary2', $cleanQuery(array_merge($summaryBase, ['issue' => 'problem']))),
                    'Муаммосиз' => route('summary2', $cleanQuery(array_merge($summaryBase, ['issue' => 'no_problem']))),
                    'Қарздорлар' => route('debts', $cleanQuery(array_merge($debtsBase, ['debtors' => 1]))),
                    default => route('summary2', $cleanQuery($summaryBase)),
                };
            }
            unset($row);
        }

        if (isset($viewData['monitoringDistrictRows']) && is_array($viewData['monitoringDistrictRows'])) {
            foreach ($viewData['monitoringDistrictRows'] as $row) {
                if (!is_object($row)) {
                    continue;
                }

                $rowDistrict = $row->district ?? null;

                if (empty($row->list_url)) {
                    $row->list_url = route('summary2', $cleanQuery(array_merge($summaryBase, ['district' => $rowDistrict])));
                }
                if (empty($row->problem_url)) {
                    $row->problem_url = route('summary2', $cleanQuery(array_merge($summaryBase, [
                        'district' => $rowDistrict,
                        'issue' => 'problem',
                    ])));
                }
                if (empty($row->no_problem_url)) {
                    $row->no_problem_url = route('summary2', $cleanQuery(array_merge($summaryBase, [
                        'district' => $rowDistrict,
                        'issue' => 'no_problem',
                    ])));
                }
                if (empty($row->debt_url)) {
                    $row->debt_url = route('debts', $cleanQuery(array_merge($debtsBase, [
                        'district' => $rowDistrict,
                        'debtors' => 1,
                    ])));
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // MONITORING TABLES DATA for dashboard
    // ──────────────────────────────────────────────────────────────────────
    private function buildMonitoringData(array $filters = []): array
    {
        $status   = $this->normalizeRequestedStatus((string) ($filters['status'] ?? 'all'));
        $issue    = $this->normalizeRequestedIssue((string) ($filters['issue'] ?? 'all'));
        $district = isset($filters['district']) ? trim((string) $filters['district']) : null;
        $search   = isset($filters['search']) ? trim((string) $filters['search']) : null;
        if ($district === '') $district = null;
        if ($search === '') $search = null;

        $cleanQuery = static function (array $params): array {
            return array_filter($params, static fn ($value) => !($value === null || $value === ''));
        };

        $buildSummaryListUrl = function (array $overrides = []) use ($cleanQuery, $district, $status, $issue, $search): string {
            $params = [
                'district' => $district,
                'status' => $status !== 'all' ? $status : null,
                'issue' => $issue !== 'all' ? $issue : null,
                'search' => $search,
            ];

            $params = array_merge($params, $overrides);
            return route('summary2', $cleanQuery($params));
        };

        $buildDebtsListUrl = function (array $overrides = []) use ($cleanQuery, $district, $status, $issue, $search): string {
            $params = [
                'district' => $district,
                'status' => $status,
                'issue' => $issue !== 'all' ? $issue : null,
                'search' => $search,
                'debt_type' => 'overdue',
                'debtors' => null,
            ];

            $params = array_merge($params, $overrides);
            return route('debts', $cleanQuery($params));
        };

        $baseWhere = [];
        $statusWhere = $this->buildContractStatusWhereSql($status, 'c');
        if ($statusWhere) $baseWhere[] = $statusWhere;
        $issueWhere = $this->buildConstructionIssueWhereSql($issue, 'c');
        if ($issueWhere) $baseWhere[] = $issueWhere;
        if ($district) $baseWhere[] = "c.district = " . DB::getPdo()->quote($district);
        if ($search) {
            $q = DB::getPdo()->quote('%' . $search . '%');
            $baseWhere[] = "(c.investor_name LIKE {$q} OR c.contract_number LIKE {$q} OR c.inn LIKE {$q} OR c.district LIKE {$q} OR CAST(c.contract_id AS CHAR) LIKE {$q})";
        }

        $filteredContracts = $this->fetchContractFinancialRows($baseWhere);

        foreach ($filteredContracts as $contract) {
            $metrics = $this->calculateContractFinancials(
                $contract->contract_value ?? 0,
                $contract->total_paid ?? 0,
                $contract->payment_schedule ?? null,
                $contract->payment_terms ?? null,
                $contract->contract_date ?? null
            );

            $contract->contract_value = (float) ($metrics['plan'] ?? 0.0);
            $contract->total_paid = (float) ($metrics['fact'] ?? 0.0);
            $contract->debt_total = (float) ($metrics['overdue_debt'] ?? 0.0);
            $contract->issue_key = $this->normalizeConstructionIssue($contract->construction_issues ?? null);
            $contract->is_full_paid = $contract->contract_value > 0.0
                && $contract->total_paid >= $contract->contract_value;
        }

        $aggregateContracts = function (array $contracts): object {
            $aggregate = (object) [
                'contracts_count' => 0,
                'contract_value' => 0.0,
                'total_paid' => 0.0,
                'debt_total' => 0.0,
            ];

            foreach ($contracts as $contract) {
                $aggregate->contracts_count++;
                $aggregate->contract_value += (float) ($contract->contract_value ?? 0.0);
                $aggregate->total_paid += (float) ($contract->total_paid ?? 0.0);
                $aggregate->debt_total += (float) ($contract->debt_total ?? 0.0);
            }

            return $aggregate;
        };

        $scopeStats = $aggregateContracts($filteredContracts);
        $scopeProblem = $aggregateContracts(array_values(array_filter(
            $filteredContracts,
            static fn ($contract) => (($contract->issue_key ?? '') === 'problem')
        )));
        $scopeClear = $aggregateContracts(array_values(array_filter(
            $filteredContracts,
            static fn ($contract) => (($contract->issue_key ?? '') === 'no_problem')
        )));
        $scopeDebt = $aggregateContracts(array_values(array_filter(
            $filteredContracts,
            static fn ($contract) => ((float) ($contract->debt_total ?? 0.0)) > 0.0
        )));
        $scopeFullPaid = $aggregateContracts(array_values(array_filter(
            $filteredContracts,
            static fn ($contract) => (bool) ($contract->is_full_paid ?? false)
        )));

        $toRow = function (string $label, object $row, ?string $listUrl = null): array {
            $plan = (float) ($row->contract_value ?? 0);
            $fact = (float) ($row->total_paid ?? 0);

            return [
                'label' => $label,
                'contracts_count' => (int) ($row->contracts_count ?? 0),
                'contract_value' => $plan,
                'total_paid' => $fact,
                'debt_total' => (float) ($row->debt_total ?? 0),
                'pct' => $plan > 0 ? round($fact / $plan * 100, 1) : 0,
                'list_url' => $listUrl,
            ];
        };

        $monitoringSummaryRows = [
            $toRow('Фильтр бўйича шартномалар', $scopeStats, $buildSummaryListUrl()),
            $toRow('Муаммоли', $scopeProblem, $buildSummaryListUrl(['issue' => 'problem'])),
            $toRow('Муаммосиз', $scopeClear, $buildSummaryListUrl(['issue' => 'no_problem'])),
            $toRow('100% бажарилган', $scopeFullPaid, $buildSummaryListUrl()),
            $toRow('Қарздорлар', $scopeDebt, $buildDebtsListUrl(['debtors' => 1, 'debt_type' => 'overdue'])),
        ];

        $districtBuckets = [];
        foreach ($filteredContracts as $contract) {
            $districtName = trim((string) ($contract->district ?? ''));
            if ($districtName === '') {
                continue;
            }

            if (!isset($districtBuckets[$districtName])) {
                $districtBuckets[$districtName] = (object) [
                    'district' => $districtName,
                    'contracts_count' => 0,
                    'contract_value' => 0.0,
                    'total_paid' => 0.0,
                    'debt_total' => 0.0,
                    'problem_count' => 0,
                    'no_problem_count' => 0,
                ];
            }

            $bucket = $districtBuckets[$districtName];
            $bucket->contracts_count++;
            $bucket->contract_value += (float) ($contract->contract_value ?? 0.0);
            $bucket->total_paid += (float) ($contract->total_paid ?? 0.0);
            $bucket->debt_total += (float) ($contract->debt_total ?? 0.0);

            if (($contract->issue_key ?? '') === 'problem') {
                $bucket->problem_count++;
            }

            if (($contract->issue_key ?? '') === 'no_problem') {
                $bucket->no_problem_count++;
            }
        }

        $monitoringDistrictRows = array_values($districtBuckets);
        usort($monitoringDistrictRows, static function ($left, $right): int {
            $debtCompare = ((float) ($right->debt_total ?? 0.0)) <=> ((float) ($left->debt_total ?? 0.0));
            if ($debtCompare !== 0) {
                return $debtCompare;
            }

            $planCompare = ((float) ($right->contract_value ?? 0.0)) <=> ((float) ($left->contract_value ?? 0.0));
            if ($planCompare !== 0) {
                return $planCompare;
            }

            return strcmp((string) ($left->district ?? ''), (string) ($right->district ?? ''));
        });

        foreach ($monitoringDistrictRows as $row) {
            $plan = (float) ($row->contract_value ?? 0);
            $fact = (float) ($row->total_paid ?? 0);
            $row->pct = $plan > 0 ? round($fact / $plan * 100, 1) : 0;
            $row->list_url = $buildSummaryListUrl(['district' => $row->district]);
            $row->problem_url = $buildSummaryListUrl([
                'district' => $row->district,
                'issue' => 'problem',
            ]);
            $row->no_problem_url = $buildSummaryListUrl([
                'district' => $row->district,
                'issue' => 'no_problem',
            ]);
            $row->debt_url = $buildDebtsListUrl([
                'district' => $row->district,
                'debtors' => 1,
                'debt_type' => 'overdue',
            ]);
        }

        $monitoringTopDebts = array_values(array_filter(
            $filteredContracts,
            static fn ($contract) => ((float) ($contract->debt_total ?? 0.0)) > 0.0
        ));
        usort($monitoringTopDebts, static function ($left, $right): int {
            $debtCompare = ((float) ($right->debt_total ?? 0.0)) <=> ((float) ($left->debt_total ?? 0.0));
            if ($debtCompare !== 0) {
                return $debtCompare;
            }

            return ((float) ($right->contract_value ?? 0.0)) <=> ((float) ($left->contract_value ?? 0.0));
        });
        $monitoringTopDebts = array_slice($monitoringTopDebts, 0, 32);

        foreach ($monitoringTopDebts as $row) {
            $row->issue_label = $this->issueStatusLabel($row->construction_issues ?? null);
        }

        $parseContractDate = static function ($rawDate): ?\Carbon\CarbonImmutable {
            $dateValue = trim((string) $rawDate);
            if ($dateValue === '') {
                return null;
            }

            try {
                return \Carbon\CarbonImmutable::parse($dateValue)->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        };

        $groupContractsByDate = function (array $contracts) use ($parseContractDate): array {
            $groups = [];

            foreach ($contracts as $contract) {
                $contractDay = $parseContractDate($contract->contract_date ?? null);
                if ($contractDay === null) {
                    continue;
                }

                $dayKey = $contractDay->format('Y-m-d');
                if (!isset($groups[$dayKey])) {
                    $groups[$dayKey] = (object) [
                        'contract_day' => $dayKey,
                        'contracts_count' => 0,
                        'contract_value' => 0.0,
                        'debt_total' => 0.0,
                    ];
                }

                $groups[$dayKey]->contracts_count++;
                $groups[$dayKey]->contract_value += (float) ($contract->contract_value ?? 0.0);
                $groups[$dayKey]->debt_total += (float) ($contract->debt_total ?? 0.0);
            }

            ksort($groups);
            return array_values($groups);
        };

        $today = \Carbon\CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $monthEnd = $monthStart->addMonth();

        $currentMonthContracts = array_values(array_filter($filteredContracts, function ($contract) use ($parseContractDate, $monthStart, $monthEnd) {
            $contractDay = $parseContractDate($contract->contract_date ?? null);
            if ($contractDay === null) {
                return false;
            }

            return $contractDay->greaterThanOrEqualTo($monthStart)
                && $contractDay->lessThan($monthEnd);
        }));

        $monitoringNewContracts = $groupContractsByDate($currentMonthContracts);

        if (empty($monitoringNewContracts)) {
            $fallbackStart = $today->subDays(31)->startOfDay();
            $fallbackContracts = array_values(array_filter($filteredContracts, function ($contract) use ($parseContractDate, $fallbackStart) {
                $contractDay = $parseContractDate($contract->contract_date ?? null);
                if ($contractDay === null) {
                    return false;
                }

                return $contractDay->greaterThanOrEqualTo($fallbackStart);
            }));

            $monitoringNewContracts = $groupContractsByDate($fallbackContracts);
        }

        $planByMonth = [];
        $scheduleQuery = DB::table('apz_contracts as c')
            ->whereNotNull('c.payment_schedule')
            ->whereRaw("c.payment_schedule != 'null' AND c.payment_schedule != '{}' AND c.payment_schedule != '[]'");
        foreach ($baseWhere as $condition) {
            $scheduleQuery->whereRaw($condition);
        }
        $scheduleRows = $scheduleQuery->get(['c.payment_schedule'])->pluck('payment_schedule');

        foreach ($scheduleRows as $scheduleJson) {
            $schedule = json_decode($scheduleJson, true);
            if (!is_array($schedule)) continue;

            foreach ($schedule as $rawDate => $amount) {
                try {
                    $ym = \Carbon\Carbon::parse($rawDate)->format('Y-m');
                    $planByMonth[$ym] = ($planByMonth[$ym] ?? 0) + (float) $amount;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        $paymentsScopeSql = $baseWhere ? (' AND ' . implode(' AND ', $baseWhere)) : '';

        $monthlyFactRows = DB::select("
            SELECT DATE_FORMAT(p.payment_date, '%Y-%m') as ym,
                   COALESCE(SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END), 0) as fact_total,
                   COALESCE(SUM(CASE WHEN p.type='АПЗ тўлови' AND p.flow='Приход' THEN p.amount ELSE 0 END), 0) as apz_payment,
                   COALESCE(SUM(CASE WHEN p.type='АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END), 0) as apz_refund,
                   COALESCE(SUM(CASE WHEN p.type='Пеня тўлови' AND p.flow='Приход' THEN p.amount ELSE 0 END), 0) as penalty_payment
            FROM apz_payments p
            LEFT JOIN apz_contracts c ON c.contract_id = p.contract_id
            WHERE p.payment_date IS NOT NULL {$paymentsScopeSql}
            GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
        ");

        $factByMonth = [];
        foreach ($monthlyFactRows as $row) {
            $factByMonth[$row->ym] = $row;
        }

        $ruMonths = [
            1=>'Январь', 2=>'Февраль', 3=>'Март', 4=>'Апрель', 5=>'Май', 6=>'Июнь',
            7=>'Июль', 8=>'Август', 9=>'Сентябрь', 10=>'Октябрь', 11=>'Ноябрь', 12=>'Декабрь',
        ];

        $allMonths = array_unique(array_merge(array_keys($planByMonth), array_keys($factByMonth)));
        sort($allMonths);
        if (count($allMonths) > 36) {
            $allMonths = array_slice($allMonths, -36);
        }

        $monitoringMonthlyRows = [];
        foreach ($allMonths as $ym) {
            [$year, $month] = explode('-', $ym);
            $monthNum = (int) $month;
            $factRow  = $factByMonth[$ym] ?? null;
            $plan     = (float) ($planByMonth[$ym] ?? 0);
            $fact     = (float) ($factRow->fact_total ?? 0);

            $monitoringMonthlyRows[] = [
                'ym'              => $ym,
                'month_label'     => ($ruMonths[$monthNum] ?? $ym) . ' ' . $year,
                'plan_total'      => $plan,
                'fact_total'      => $fact,
                'apz_payment'     => (float) ($factRow->apz_payment ?? 0),
                'apz_refund'      => (float) ($factRow->apz_refund ?? 0),
                'penalty_payment' => (float) ($factRow->penalty_payment ?? 0),
                'plan_fact_diff'  => $plan - $fact,
            ];
        }

        $monitoringDailyRows = DB::select("
            SELECT DATE(p.payment_date) as pay_day,
                   COALESCE(SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END), 0) as total_income,
                   COALESCE(SUM(CASE WHEN p.type='АПЗ тўлови' AND p.flow='Приход' THEN p.amount ELSE 0 END), 0) as apz_payment,
                   COALESCE(SUM(CASE WHEN p.type='АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END), 0) as apz_refund,
                   COALESCE(SUM(CASE WHEN p.type='Пеня тўлови' AND p.flow='Приход' THEN p.amount ELSE 0 END), 0) as penalty_payment
            FROM apz_payments p
            LEFT JOIN apz_contracts c ON c.contract_id = p.contract_id
            WHERE p.payment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND p.payment_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
              {$paymentsScopeSql}
            GROUP BY DATE(p.payment_date)
            ORDER BY DATE(p.payment_date)
        ");

        return compact(
            'monitoringSummaryRows',
            'monitoringDistrictRows',
            'monitoringTopDebts',
            'monitoringNewContracts',
            'monitoringMonthlyRows',
            'monitoringDailyRows'
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER: Build drill-down data for dashboard (shared with summary)
    // ──────────────────────────────────────────────────────────────────────
    private function buildSummaryDrillDown($year = null)
    {
        $yearCond = $year ? 'AND p.year = ' . (int) $year : '';

        $ruMonths = [
            1=>'Январь', 2=>'Февраль', 3=>'Март',
            4=>'Апрель', 5=>'Май', 6=>'Июнь',
            7=>'Июль', 8=>'Август', 9=>'Сентябрь',
            10=>'Октябрь', 11=>'Ноябрь', 12=>'Декабрь',
        ];

        // ── 1. District payment totals ──────────────────────────────────────
        $payRows = DB::select("
            SELECT p.district,
                COUNT(DISTINCT p.contract_id) as contract_count,
                SUM(CASE WHEN p.type='АПЗ тўлови'            AND p.flow='Приход' THEN p.amount ELSE 0 END) as apz_payment,
                SUM(CASE WHEN p.type='Пеня тўлови'           AND p.flow='Приход' THEN p.amount ELSE 0 END) as penalty,
                SUM(CASE WHEN p.type='АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END) as refund,
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END) as total_income
            FROM apz_payments p
            WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
            GROUP BY p.district
            ORDER BY total_income DESC
        ");

        // ── 2. Plan per district (deduplicated by contract) ──────────────────
        $planFactRows = DB::select("
            SELECT p.district,
                SUM(c_plan.plan) as plan_value,
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END) as fact_paid
            FROM apz_payments p
            INNER JOIN (
                SELECT contract_id, MAX(contract_value) as plan
                FROM apz_contracts GROUP BY contract_id
            ) c_plan ON c_plan.contract_id = p.contract_id
            WHERE p.district IS NOT NULL AND p.district != ''
            GROUP BY p.district
        ");
        $planFact = [];
        foreach ($planFactRows as $r) {
            $plan = (float) $r->plan_value;
            $fact = (float) $r->fact_paid;
            $planFact[$r->district] = [
                'plan'    => $plan,
                'fact'    => $fact,
                'pct'     => $plan > 0 ? round($fact / $plan * 100, 1) : 0,
                'balance' => $plan - $fact,
            ];
        }

        // ── 3. Build summaryData + grand totals ───────────────────────────
        $totals = ['apz_payment'=>0,'penalty'=>0,'refund'=>0,'total_income'=>0,'contract_count'=>0];
        $summaryData = [];
        foreach ($payRows as $r) {
            $summaryData[] = [
                'district'       => $r->district,
                'contract_count' => (int)   $r->contract_count,
                'apz_payment'    => (float)  $r->apz_payment,
                'penalty'        => (float)  $r->penalty,
                'refund'         => (float)  $r->refund,
                'total_income'   => (float)  $r->total_income,
            ];
            $totals['apz_payment']    += (float) $r->apz_payment;
            $totals['penalty']        += (float) $r->penalty;
            $totals['refund']         += (float) $r->refund;
            $totals['total_income']   += (float) $r->total_income;
            $totals['contract_count'] += (int)   $r->contract_count;
        }

        // ── 4. Fact payments by year ─ month ─ day ─ district ──────────────
        $factRows = DB::select("
            SELECT p.year, p.month, DATE(p.payment_date) as pay_day, p.district,
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END) as income,
                SUM(CASE WHEN p.type='АПЗ тўлови'            AND p.flow='Приход' THEN p.amount ELSE 0 END) as apz,
                SUM(CASE WHEN p.type='Пеня тўлови'           AND p.flow='Приход' THEN p.amount ELSE 0 END) as pen,
                SUM(CASE WHEN p.type='АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END) as ref,
                COUNT(DISTINCT p.contract_id) as cnt
            FROM apz_payments p
            WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
            GROUP BY p.year, p.month, pay_day, p.district
            ORDER BY p.year, MIN(p.payment_date), p.district
        ");

        // factMap[year][month][day][district]
        $factMap = [];
        foreach ($factRows as $r) {
            $factMap[$r->year][$r->month][$r->pay_day][$r->district] = $r;
        }

        // ── 5. Fact totals by year+district and year+month+district ────────
        $pfYearRows = DB::select("
            SELECT p.year, p.district,
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END) as fact_paid
            FROM apz_payments p
            WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
            GROUP BY p.year, p.district
        ");
        $pfYear = [];
        foreach ($pfYearRows as $r) {
            $pfYear[$r->year][$r->district] = (float) $r->fact_paid;
        }

        $pfMonthRows = DB::select("
            SELECT p.year, p.month, p.district,
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END) as fact_paid
            FROM apz_payments p
            WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
            GROUP BY p.year, p.month, p.district
        ");
        $pfMonth = [];
        foreach ($pfMonthRows as $r) {
            $pfMonth[$r->year][$r->month][$r->district] = (float) $r->fact_paid;
        }

        // ── 6. Parse payment_schedule → planMap[district][year][month][day] ─────
        $contracts = DB::table('apz_contracts')
            ->whereNotNull('payment_schedule')
            ->whereRaw("payment_schedule != 'null' AND payment_schedule != '{}' AND payment_schedule != '[]'")
            ->get(['contract_id', 'payment_schedule']);

        $contractDistrict = DB::table('apz_payments')
            ->whereNotNull('district')->where('district', '!=', '')
            ->groupBy('contract_id')
            ->selectRaw('contract_id, MAX(district) as district')
            ->pluck('district', 'contract_id')
            ->toArray();

        $planMap = [];
        foreach ($contracts as $c) {
            $sched = json_decode($c->payment_schedule, true);
            if (!is_array($sched) || empty($sched)) continue;
            $dist = $contractDistrict[$c->contract_id] ?? null;
            if (!$dist) continue;
            foreach ($sched as $rawDate => $amountSom) {
                try {
                    $dt = \Carbon\Carbon::parse($rawDate);
                } catch (\Exception $e) {
                    continue;
                }
                $yy  = $dt->year;
                $mm  = $ruMonths[$dt->month];
                $day = $dt->format('Y-m-d');
                $planMap[$dist][$yy][$mm][$day] = ($planMap[$dist][$yy][$mm][$day] ?? 0)
                    + round($amountSom, 4);
            }
        }

        // ── 7a. Build plan totals per (district,year) and (district,year,month) from planMap
        $pfYearPlan  = []; // [dist][year]       = scheduled plan sum
        $pfMonthPlan = []; // [dist][year][month] = scheduled plan sum
        foreach ($planMap as $dist => $years) {
            foreach ($years as $yy => $months) {
                foreach ($months as $mm => $days) {
                    $sum = array_sum($days);
                    $pfYearPlan[$dist][$yy]          = ($pfYearPlan[$dist][$yy] ?? 0) + $sum;
                    $pfMonthPlan[$dist][$yy][$mm]    = ($pfMonthPlan[$dist][$yy][$mm] ?? 0) + $sum;
                }
            }
        }

        // ── 7. Pre-build dayRows[district][year][month] ──────────────────────
        $dayRows = [];

        // Gather all (district, year, month) combinations from facts
        $monthCombos = [];
        foreach ($factRows as $r) {
            $monthCombos[$r->district][$r->year][$r->month] = true;
        }
        // Also include months that only appear in the plan schedule
        foreach ($planMap as $dist => $years) {
            foreach ($years as $yy => $months) {
                foreach ($months as $mm => $_) {
                    $monthCombos[$dist][$yy][$mm] = true;
                }
            }
        }

        foreach ($monthCombos as $dist => $years) {
            foreach ($years as $yy => $months) {
                foreach ($months as $mm => $_) {
                    // Days where THIS district had a fact payment
                    $factDays = array_keys(array_filter(
                        $factMap[$yy][$mm] ?? [],
                        fn($dayDistricts) => isset($dayDistricts[$dist])
                    ));
                    $planDays = array_keys($planMap[$dist][$yy][$mm] ?? []);
                    $allDays  = array_unique(array_merge($factDays, $planDays));
                    sort($allDays);

                    $rows = [];
                    foreach ($allDays as $dayKey) {
                        $fr      = $factMap[$yy][$mm][$dayKey][$dist] ?? null;
                        $planAmt = round($planMap[$dist][$yy][$mm][$dayKey] ?? 0, 2);
                        $factAmt = round($fr ? (float) $fr->income : 0, 2);
                        $hasPlan = $planAmt > 0;
                        $hasFact = $factAmt > 0;
                        $bal     = $hasPlan ? round($planAmt - $factAmt, 2) : null;
                        $pct     = ($hasPlan && $planAmt > 0)
                            ? round($factAmt / $planAmt * 100, 1)
                            : null;
                        $rows[] = [
                            'date'     => $dayKey,
                            'date_fmt' => date('d.m.Y', strtotime($dayKey)),
                            'type'     => $hasPlan && $hasFact ? 'both'
                                        : ($hasPlan ? 'plan' : 'fact'),
                            'cnt'      => $fr ? (int) $fr->cnt : 0,
                            'income'   => $hasFact ? $factAmt : null,
                            'apz'      => $fr && (float) $fr->apz  > 0 ? round((float) $fr->apz,  2) : null,
                            'pen'      => $fr && (float) $fr->pen  > 0 ? round((float) $fr->pen,  2) : null,
                            'ref'      => $fr && (float) $fr->ref  > 0 ? round((float) $fr->ref,  2) : null,
                            'plan'     => $hasPlan ? $planAmt : null,
                            'fact'     => $hasFact ? $factAmt : null,
                            'balance'  => $bal,
                            'pct'      => $pct,
                            'bar_w'    => $pct !== null ? min($pct, 100) : 0,
                        ];
                    }
                    $dayRows[$dist][$yy][$mm] = $rows;
                }
                // Sort months in calendar order
                $monthOrder = array_flip(array_values($ruMonths));
                uksort($dayRows[$dist][$yy], fn($a,$b) => ($monthOrder[$a] ?? 99) <=> ($monthOrder[$b] ?? 99));
            }
            // Sort years ascending
            ksort($dayRows[$dist]);
        }

        // ── 8. Available years ────────────────────────────────────────
        $availableYears = DB::table('apz_payments')
            ->selectRaw('DISTINCT year')->whereNotNull('year')->where('year', '>', 0)
            ->orderBy('year', 'desc')->pluck('year')->toArray();

        return compact(
            'summaryData', 'totals', 'planFact',
            'availableYears', 'factMap', 'dayRows',
            'pfYear', 'pfMonth', 'pfYearPlan', 'pfMonthPlan'
        );
    }
}
