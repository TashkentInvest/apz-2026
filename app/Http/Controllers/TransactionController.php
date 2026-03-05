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

        $dashboardCacheKey = 'apz_dashboard_data_v5_' . md5(
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

            $contractStats = DB::selectOne("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN {$completedExpr} THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN {$cancelledExpr} THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN (NOT {$cancelledExpr} AND NOT {$completedExpr}) THEN 1 ELSE 0 END) as active,
                    SUM(contract_value) as total_value
                FROM apz_contracts
            ");

            $debtorsStats = DB::selectOne("
                SELECT
                    SUM(CASE WHEN (NOT {$cancelledExpr} AND NOT {$completedExpr})
                              AND COALESCE(c.contract_value, 0) > COALESCE(paid.total_paid, 0)
                             THEN 1 ELSE 0 END) as debtors_count,
                    SUM(CASE WHEN (NOT {$cancelledExpr} AND NOT {$completedExpr})
                             THEN GREATEST(COALESCE(c.contract_value, 0) - COALESCE(paid.total_paid, 0), 0)
                             ELSE 0 END) as debt_total
                FROM apz_contracts c
                LEFT JOIN (
                    SELECT contract_id, SUM(amount) as total_paid
                    FROM apz_payments
                    WHERE flow = 'Приход'
                    GROUP BY contract_id
                ) paid ON paid.contract_id = c.contract_id
            ");

            // Monthly income — last 18 months
            $monthlyStats = DB::select("
                SELECT year, month,
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) / 1000000 as income,
                    SUM(CASE WHEN flow='Расход' THEN amount ELSE 0 END) / 1000000 as expense,
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
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) / 1000000 as income,
                    COUNT(*) as cnt
                FROM apz_payments
                WHERE district IS NOT NULL AND district != ''
                GROUP BY district
                ORDER BY income DESC
            ");

            // Payment type breakdown
            $typeStats = DB::select("
                SELECT type,
                    SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) / 1000000 as income,
                    COUNT(*) as cnt
                FROM apz_payments
                WHERE type IS NOT NULL AND type != ''
                GROUP BY type
                ORDER BY income DESC
            ");

            // Plan vs Fact — contracts with total planned vs total paid (joined)
            $planFact = DB::select("
                SELECT c.district,
                    SUM(c.contract_value) / 1000000 as plan_total,
                    SUM(COALESCE(paid.total_paid, 0)) / 1000000 as fact_total
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
                       c.contract_value, c.payment_terms, c.installments_count,
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
                $grandPlan += (float) $c->contract_value;
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
                'grand_plan_mln' => $this->formatNumber($grandPlan / 1000000, 1),
                'grand_fact_mln' => $this->formatNumber($grandFact / 1000000, 1),
                'overall_pct' => $this->formatNumber($overallPct, 1),
                'overall_pct_class' => $overallPct >= 100 ? 'txt-good' : 'txt-warn',
            ],
            'summaryRow' => [
                'plan_mln' => $this->formatNumber($grandPlan / 1000000, 2),
                'fact_mln' => $this->formatNumber($grandFact / 1000000, 2),
                'balance_mln' => $this->formatNumber($summaryRowBalance / 1000000, 2),
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
        $district    = $request->filled('district') ? trim((string) $request->district) : null;
        $searchTerm  = $request->filled('search') ? trim((string) $request->search) : null;
        $onlyDebtors = (int) $request->get('debtors', 0) === 1;
        $page        = max(1, (int) $request->get('page', 1));
        $perPage     = 25;

        $cacheKey = 'apz_debts_' . md5(
            $status . '|' .
            $issue . '|' .
            ($district ?? 'all') . '|' .
            mb_strtolower($searchTerm ?? '') . '|' .
            ($onlyDebtors ? 'debtors' : 'all')
        );

        $allData = Cache::remember($cacheKey, self::CACHE_REPORT, function () use ($status, $issue, $district, $searchTerm, $onlyDebtors) {
            $where = [];
            $statusWhere = $this->buildContractStatusWhereSql($status, 'c');
            if ($statusWhere) $where[] = $statusWhere;
            $issueWhere = $this->buildConstructionIssueWhereSql($issue, 'c');
            if ($issueWhere) $where[] = $issueWhere;
            if ($district) $where[] = "c.district = " . DB::getPdo()->quote($district);
            if ($onlyDebtors) $where[] = '(COALESCE(c.contract_value, 0) > COALESCE(paid.total_paid, 0))';
            if ($searchTerm) {
                $q = DB::getPdo()->quote('%' . $searchTerm . '%');
                $where[] = "(c.investor_name LIKE {$q} OR c.contract_number LIKE {$q} OR c.inn LIKE {$q} OR CAST(c.contract_id AS CHAR) LIKE {$q})";
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $contracts = DB::select("
                SELECT c.id, c.contract_id, c.investor_name, c.district,
                       c.contract_number, c.contract_date, c.contract_status,
                       c.construction_issues, c.contract_value,
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

            foreach ($contracts as $contract) {
                $plan = (float) ($contract->contract_value ?? 0);
                $fact = (float) ($contract->total_paid ?? 0);
                $diff = $plan - $fact;

                $contract->status_key      = $this->normalizeContractStatus($contract->contract_status ?? null);
                $contract->status_label    = $this->contractStatusLabel($contract->contract_status ?? null);
                $contract->issue_key       = $this->normalizeConstructionIssue($contract->construction_issues ?? null);
                $contract->issue_label     = $this->issueStatusLabel($contract->construction_issues ?? null);
                $contract->debt            = max($diff, 0);
                $contract->plan_fact_diff  = $diff;

                $grandPlan += $plan;
                $grandFact += $fact;
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
            return $this->exportDebtsXlsx(
                (array) ($allData['contracts'] ?? []),
                $status,
                $issue,
                $district,
                $searchTerm,
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
        $grandDebt = max($grandPlan - $grandFact, 0);
        $grandPct = $grandPlan > 0 ? round(($grandFact / $grandPlan) * 100, 1) : null;

        $paginationQuery = [
            'status' => $status,
            'district' => $district,
            'issue' => $issue !== 'all' ? $issue : null,
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
                'grand_plan_mln' => $this->formatNumber($grandPlan / 1000000, 2),
                'grand_debt_mln' => $this->formatNumber($grandDebt / 1000000, 2),
            ],
            'summaryRow' => [
                'plan_mln' => $this->formatNumber($grandPlan / 1000000, 2),
                'fact_mln' => $this->formatNumber($grandFact / 1000000, 2),
                'debt_mln' => $this->formatNumber($grandDebt / 1000000, 2),
                'diff_mln' => $this->formatNumber(($grandPlan - $grandFact) / 1000000, 2),
                'diff_class' => $this->completionBandClass($grandPct),
            ],
            'districtOptions' => $this->buildDistrictOptions($allData['availableDistricts'] ?? [], $district, 'Туман: барчаси'),
            'statusOptions' => $this->buildStatusOptions($status),
            'issueOptions' => $this->buildIssueOptions($issue, 'Муаммо ҳолати: барчаси'),
            'debtorsOptions' => $this->buildDebtorOptions($onlyDebtors),
            'selectedStatus' => $status,
            'selectedIssue' => $issue,
            'selectedDistrict' => $district,
            'searchTerm' => $searchTerm,
            'onlyDebtors' => $onlyDebtors,
            'showResetFilters' => ($status !== 'in_progress' || $issue !== 'all' || $district !== null || !empty($searchTerm) || $onlyDebtors),
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
        $plan     = (float) ($contract->contract_value ?? 0);
        $fact     = (float) ($contract->total_paid ?? 0);
        $diff     = $plan - $fact;

        $contract->status_key   = $this->normalizeContractStatus($contract->contract_status ?? null);
        $contract->status_label = $this->contractStatusLabel($contract->contract_status ?? null);
        $contract->issue_key    = $this->normalizeConstructionIssue($contract->construction_issues ?? null);
        $contract->issue_label  = $this->issueStatusLabel($contract->construction_issues ?? null);
        $contract->debt         = max($diff, 0);
        $contract->pct          = $plan > 0 ? round(($fact / $plan) * 100, 1) : 0;

        $backUrl = $request->filled('back') ? (string) $request->back : null;

        $scheduleRows = [];
        foreach ($payload['schedule'] as $index => $row) {
            $scheduleRows[] = [
                'row_num' => $index + 1,
                'date' => $row['date'] ?? '—',
                'amount' => $this->formatNumber((float) ($row['amount'] ?? 0), 4),
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
                'inn' => $contract->inn ?: '—',
                'contract_date' => $this->formatDate($contract->contract_date ?? null),
                'status_label' => $contract->status_label ?? 'Амалдаги',
                'status_class' => $this->statusClass($statusKey, 'st-inprogress'),
                'issue_label' => $contract->issue_label ?? '—',
                'issue_class' => str_replace('issue-', 'is-', $this->issueClass($issueKey)),
                'plan_mln' => $this->formatNumber($plan / 1000000, 2),
                'fact_mln' => $this->formatNumber($fact / 1000000, 2),
                'debt_mln' => $this->formatNumber(((float) ($contract->debt ?? 0)) / 1000000, 2),
                'pct' => $this->formatNumber((float) ($contract->pct ?? 0), 1),
            ],
            'scheduleRows' => $scheduleRows,
            'payments' => $this->mapContractPaymentsForView($payload['payments'], (int) $payload['page'], (int) $payload['per_page']),
            'total' => (int) ($payload['total'] ?? 0),
            'lastPage' => (int) ($payload['last_page'] ?? 1),
            'backUrl' => $backUrl ?: route('summary2'),
            'pagination' => $this->buildContractPagination((int) ($contract->contract_id ?? 0), $backUrl, (int) $payload['page'], (int) $payload['last_page']),
        ]);
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
                            'amount' => round((float) $amt / 1000000, 4),
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
                'plan_mln' => $plan > 0 ? $this->formatNumber($plan / 1000000, 2) : '—',
                'payment_terms' => $contract->payment_terms ?: '—',
                'installments_count' => $contract->installments_count ?: '—',
                'fact_mln' => $fact > 0 ? $this->formatNumber($fact / 1000000, 2) : '—',
                'balance_mln' => $plan > 0 ? $this->formatNumber($balance / 1000000, 2) : '—',
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
                'plan_mln' => $plan > 0 ? $this->formatNumber($plan / 1000000, 2) : '—',
                'fact_mln' => $fact > 0 ? $this->formatNumber($fact / 1000000, 2) : '—',
                'debt_mln' => $debt > 0 ? $this->formatNumber($debt / 1000000, 2) : '0.00',
                'diff_mln' => $this->formatNumber($diff / 1000000, 2),
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
                'amount_signed_mln' => ($isIn ? '+' : '-') . $this->formatNumber(((float) ($payment->amount ?? 0)) / 1000000, 4),
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
        $contractColumns = array_map(
            static fn ($row) => (string) $row->Field,
            DB::select('SHOW COLUMNS FROM apz_contracts')
        );

        $paymentColumns = array_map(
            static fn ($row) => (string) $row->Field,
            DB::select('SHOW COLUMNS FROM apz_payments')
        );

        $contractSelect = implode(', ', array_map(
            static fn ($column) => "c.`{$column}` as `contract_{$column}`",
            $contractColumns
        ));

        $paymentSelect = implode(', ', array_map(
            static fn ($column) => "p.`{$column}` as `payment_{$column}`",
            $paymentColumns
        ));

        $query = "SELECT {$contractSelect}, {$paymentSelect},
                        COALESCE(agg.income_total, 0) as agg_income_total,
                        COALESCE(agg.expense_total, 0) as agg_expense_total,
                        COALESCE(agg.payment_count, 0) as agg_payment_count,
                        agg.last_payment_date as agg_last_payment_date,
                        GREATEST(COALESCE(c.contract_value, 0) - COALESCE(agg.income_total, 0), 0) as agg_debt_total
                  FROM apz_contracts c
                  LEFT JOIN (
                      SELECT contract_id,
                             SUM(CASE WHEN flow='Приход' THEN amount ELSE 0 END) as income_total,
                             SUM(CASE WHEN flow='Расход' THEN amount ELSE 0 END) as expense_total,
                             COUNT(*) as payment_count,
                             MAX(payment_date) as last_payment_date
                      FROM apz_payments
                      GROUP BY contract_id
                  ) agg ON agg.contract_id = c.contract_id
                  LEFT JOIN apz_payments p ON p.contract_id = c.contract_id
                  {$whereSQL}
                  ORDER BY c.contract_id, p.payment_date DESC, p.id DESC";

        $rows = DB::select($query, $params);

        $header = array_merge(
            ['contract_status_label', 'contract_issue_label', 'contract_schedule_flat_mln', 'agg_fact_income_mln', 'agg_fact_expense_mln', 'agg_debt_mln', 'agg_payment_count', 'agg_last_payment_date'],
            array_map(static fn ($column) => 'contract_' . $column, $contractColumns),
            array_map(static fn ($column) => 'payment_' . $column, $paymentColumns)
        );

        $sheetRows = [
            ['Filter', 'Value'],
            ['district', (string) ($request->district ?: 'all')],
            ['year', (string) ($request->year ?: 'all')],
            ['month', (string) ($request->month ?: 'all')],
            ['type', (string) ($request->type ?: 'all')],
            ['date_from', (string) ($request->date_from ?: '—')],
            ['date_to', (string) ($request->date_to ?: '—')],
            ['search', (string) ($request->search ?: '—')],
            ['exported_at', now()->format('d.m.Y H:i:s')],
            [],
            $header,
        ];

        foreach ($rows as $row) {
            $statusRaw = $row->contract_contract_status ?? null;
            $issueRaw = $row->contract_construction_issues ?? null;
            $scheduleRaw = $row->contract_payment_schedule ?? null;

            $base = [
                $this->contractStatusLabel($statusRaw),
                $this->issueStatusLabel($issueRaw),
                $this->flattenScheduleForExport($scheduleRaw),
                round(((float) ($row->agg_income_total ?? 0)) / 1000000, 4),
                round(((float) ($row->agg_expense_total ?? 0)) / 1000000, 4),
                round(((float) ($row->agg_debt_total ?? 0)) / 1000000, 4),
                (int) ($row->agg_payment_count ?? 0),
                $this->formatDate($row->agg_last_payment_date ?? null),
            ];

            $contractValues = [];
            foreach ($contractColumns as $column) {
                $contractValues[] = $row->{'contract_' . $column} ?? '';
            }

            $paymentValues = [];
            foreach ($paymentColumns as $column) {
                $paymentValues[] = $row->{'payment_' . $column} ?? '';
            }

            $sheetRows[] = array_merge($base, $contractValues, $paymentValues);
        }

        $fileName = 'system_all_in_one_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => 'All_In_One', 'rows' => $sheetRows],
        ]);
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

            $parts[] = $formattedDate . ': ' . $this->formatNumber(((float) $amount) / 1000000, 4);
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
            'Шартнома қиймати (млн)',
            'Факт тўлов (млн)',
            'Қолдиқ (млн)',
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
                round($plan / 1000000, 2),
                round($fact / 1000000, 2),
                round($balance / 1000000, 2),
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

    private function exportDebtsXlsx(array $contracts, string $status, string $issue, ?string $district, ?string $searchTerm, bool $onlyDebtors)
    {
        $rows = [[
            'ID',
            'Компания',
            'Туман',
            'Шартнома рақами',
            'Шартнома санаси',
            'Ҳолат',
            'Қурилиш ҳолати',
            'Шартнома қиймати (млн)',
            'Факт тўлов (млн)',
            'Қарздорлик (млн)',
            'План-Факт (млн)',
            'Бажарилиш %',
        ]];

        foreach ($contracts as $contract) {
            $plan = (float) ($contract->contract_value ?? 0);
            $fact = (float) ($contract->total_paid ?? 0);
            $debt = (float) ($contract->debt ?? max($plan - $fact, 0));
            $diff = (float) ($contract->plan_fact_diff ?? ($plan - $fact));
            $pct = $plan > 0 ? round(($fact / $plan) * 100, 1) : 0;

            $rows[] = [
                (int) ($contract->contract_id ?? 0),
                (string) ($contract->investor_name ?? ''),
                (string) ($contract->district ?? ''),
                (string) ($contract->contract_number ?? ''),
                $this->formatDate($contract->contract_date ?? null),
                $contract->status_label ?? $this->contractStatusLabel($contract->contract_status ?? null),
                $contract->issue_label ?? $this->issueStatusLabel($contract->construction_issues ?? null),
                round($plan / 1000000, 2),
                round($fact / 1000000, 2),
                round($debt / 1000000, 2),
                round($diff / 1000000, 2),
                $pct,
            ];
        }

        $filterRows = [
            ['Фильтр', 'Қиймат'],
            ['Туман', $district ?: 'Барчаси'],
            ['Ҳолат', $status],
            ['Муаммо', $issue],
            ['Қарздорлар', $onlyDebtors ? 'Фақат қарздорлар' : 'Барчаси'],
            ['Қидирув', $searchTerm ?: '—'],
            ['Экспорт санаси', now()->format('d.m.Y H:i')],
        ];

        $fileName = 'debts_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => 'Filters', 'rows' => $filterRows],
            ['name' => 'Debts', 'rows' => $rows],
        ]);
    }

    private function exportDashboardXlsx(array $viewData)
    {
        $summaryRows = [[
            'Номи', 'Шартномалар сони', 'Шартномалар қиймати (млн)', 'Жами тушум (млн)', 'Жами қарздорлик (млн)', 'Бажарилиш %'
        ]];
        foreach (($viewData['monitoringSummaryRows'] ?? []) as $row) {
            $summaryRows[] = [
                (string) ($row['label'] ?? ''),
                (int) ($row['contracts_count'] ?? 0),
                round(((float) ($row['contract_value'] ?? 0)) / 1000000, 2),
                round(((float) ($row['total_paid'] ?? 0)) / 1000000, 2),
                round(((float) ($row['debt_total'] ?? 0)) / 1000000, 2),
                (float) ($row['pct'] ?? 0),
            ];
        }

        $districtRows = [[
            'Туман', 'Шартномалар сони', 'Шартнома қиймати (млн)', 'Жами тушум (млн)', 'Қарздорлик (млн)', 'Муаммоли', 'Муаммосиз', 'Бажарилиш %'
        ]];
        foreach (($viewData['monitoringDistrictRows'] ?? []) as $row) {
            $districtRows[] = [
                (string) ($row->district ?? ''),
                (int) ($row->contracts_count ?? 0),
                round(((float) ($row->contract_value ?? 0)) / 1000000, 2),
                round(((float) ($row->total_paid ?? 0)) / 1000000, 2),
                round(((float) ($row->debt_total ?? 0)) / 1000000, 2),
                (int) ($row->problem_count ?? 0),
                (int) ($row->no_problem_count ?? 0),
                (float) ($row->pct ?? 0),
            ];
        }

        $topDebtRows = [[
            'Компания', 'Туман', 'Шартнома', 'Қарздорлик (млн)', 'Муаммо'
        ]];
        foreach (($viewData['monitoringTopDebts'] ?? []) as $row) {
            $topDebtRows[] = [
                (string) ($row->investor_name ?? ''),
                (string) ($row->district ?? ''),
                (string) ($row->contract_number ?? ''),
                round(((float) ($row->debt_total ?? 0)) / 1000000, 2),
                (string) ($row->issue_label ?? '—'),
            ];
        }

        $newContractRows = [[
            'Сана', 'Шартнома сони', 'Шартнома қиймати (млн)', 'Шартнома қарзи (млн)'
        ]];
        foreach (($viewData['monitoringNewContracts'] ?? []) as $row) {
            $newContractRows[] = [
                $this->formatDate($row->contract_day ?? null),
                (int) ($row->contracts_count ?? 0),
                round(((float) ($row->contract_value ?? 0)) / 1000000, 2),
                round(((float) ($row->debt_total ?? 0)) / 1000000, 2),
            ];
        }

        $monthlyRows = [[
            'Ой', 'План (млн)', 'Факт (млн)', 'АПЗ тўлов (млн)', 'Пеня (млн)', 'Қайтариш (млн)', 'План-Факт (млн)'
        ]];
        foreach (($viewData['monitoringMonthlyRows'] ?? []) as $row) {
            $monthlyRows[] = [
                (string) ($row['month_label'] ?? ''),
                round(((float) ($row['plan_total'] ?? 0)) / 1000000, 2),
                round(((float) ($row['fact_total'] ?? 0)) / 1000000, 2),
                round(((float) ($row['apz_payment'] ?? 0)) / 1000000, 2),
                round(((float) ($row['penalty_payment'] ?? 0)) / 1000000, 2),
                round(((float) ($row['apz_refund'] ?? 0)) / 1000000, 2),
                round(((float) ($row['plan_fact_diff'] ?? 0)) / 1000000, 2),
            ];
        }

        $dailyRows = [[
            'Кун', 'Жами (млн)', 'АПЗ тўлови (млн)', 'Қайтариш (млн)', 'Пеня (млн)'
        ]];
        foreach (($viewData['monitoringDailyRows'] ?? []) as $row) {
            $dailyRows[] = [
                $this->formatDate($row->pay_day ?? null),
                round(((float) ($row->total_income ?? 0)) / 1000000, 2),
                round(((float) ($row->apz_payment ?? 0)) / 1000000, 2),
                round(((float) ($row->apz_refund ?? 0)) / 1000000, 2),
                round(((float) ($row->penalty_payment ?? 0)) / 1000000, 2),
            ];
        }

        $filtersRows = [
            ['Фильтр', 'Қиймат'],
            ['Туман', (string) ($viewData['selectedMonitoringDistrict'] ?? 'Барчаси') ?: 'Барчаси'],
            ['Ҳолат', (string) ($viewData['selectedMonitoringStatus'] ?? 'all')],
            ['Муаммо', (string) ($viewData['selectedMonitoringIssue'] ?? 'all')],
            ['Қидирув', (string) ($viewData['monitoringSearch'] ?? '—') ?: '—'],
            ['Экспорт санаси', now()->format('d.m.Y H:i')],
        ];

        $fileName = 'dashboard_monitoring_' . now()->format('Ymd_His') . '.xlsx';

        return app(SimpleXlsxExportService::class)->download($fileName, [
            ['name' => 'Filters', 'rows' => $filtersRows],
            ['name' => 'Summary', 'rows' => $summaryRows],
            ['name' => 'Districts', 'rows' => $districtRows],
            ['name' => 'TopDebts', 'rows' => $topDebtRows],
            ['name' => 'NewContracts', 'rows' => $newContractRows],
            ['name' => 'Monthly', 'rows' => $monthlyRows],
            ['name' => 'Daily', 'rows' => $dailyRows],
        ]);
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
                'debtors' => null,
            ];

            $params = array_merge($params, $overrides);
            return route('debts', $cleanQuery($params));
        };

        $problemWhere   = $this->buildConstructionIssueWhereSql('problem', 'c');
        $noProblemWhere = $this->buildConstructionIssueWhereSql('no_problem', 'c');

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

        $paidJoinSql = "
            LEFT JOIN (
                SELECT contract_id, SUM(amount) as total_paid
                FROM apz_payments
                WHERE flow = 'Приход'
                GROUP BY contract_id
            ) paid ON paid.contract_id = c.contract_id
        ";

        $statsSelect = "
            COUNT(*) as contracts_count,
            COALESCE(SUM(c.contract_value), 0) as contract_value,
            COALESCE(SUM(COALESCE(paid.total_paid, 0)), 0) as total_paid,
            COALESCE(SUM(GREATEST(COALESCE(c.contract_value, 0) - COALESCE(paid.total_paid, 0), 0)), 0) as debt_total
        ";

        $fetchStats = function (array $conditions) use ($paidJoinSql, $statsSelect) {
            $clean = array_values(array_filter($conditions, fn ($c) => is_string($c) && trim($c) !== ''));
            $whereSql = $clean ? 'WHERE ' . implode(' AND ', $clean) : '';
            return DB::selectOne("SELECT {$statsSelect} FROM apz_contracts c {$paidJoinSql} {$whereSql}");
        };

        $scopeStats    = $fetchStats($baseWhere);
        $scopeProblem  = $fetchStats(array_merge($baseWhere, [$problemWhere]));
        $scopeClear    = $fetchStats(array_merge($baseWhere, [$noProblemWhere]));
        $scopeDebt     = $fetchStats(array_merge($baseWhere, ['(COALESCE(c.contract_value, 0) > COALESCE(paid.total_paid, 0))']));
        $scopeFullPaid = $fetchStats(array_merge($baseWhere, ['(COALESCE(c.contract_value, 0) > 0 AND COALESCE(paid.total_paid, 0) >= COALESCE(c.contract_value, 0))']));

        $toRow = function (string $label, object $row, ?string $listUrl = null) {
            $plan = (float) ($row->contract_value ?? 0);
            $fact = (float) ($row->total_paid ?? 0);
            return [
                'label'           => $label,
                'contracts_count' => (int) ($row->contracts_count ?? 0),
                'contract_value'  => $plan,
                'total_paid'      => $fact,
                'debt_total'      => (float) ($row->debt_total ?? 0),
                'pct'             => $plan > 0 ? round($fact / $plan * 100, 1) : 0,
                'list_url'        => $listUrl,
            ];
        };

        $monitoringSummaryRows = [
            $toRow('Фильтр бўйича шартномалар', $scopeStats, $buildSummaryListUrl()),
            $toRow('Муаммоли', $scopeProblem, $buildSummaryListUrl(['issue' => 'problem'])),
            $toRow('Муаммосиз', $scopeClear, $buildSummaryListUrl(['issue' => 'no_problem'])),
            $toRow('100% бажарилган', $scopeFullPaid, $buildSummaryListUrl()),
            $toRow('Қарздорлар', $scopeDebt, $buildDebtsListUrl(['debtors' => 1])),
        ];

        $districtConditions = array_merge($baseWhere, ["c.district IS NOT NULL AND c.district != ''"]);
        $districtWhereSql = 'WHERE ' . implode(' AND ', $districtConditions);

        $monitoringDistrictRows = DB::select("
            SELECT c.district,
                   COUNT(*) as contracts_count,
                   COALESCE(SUM(c.contract_value), 0) as contract_value,
                   COALESCE(SUM(COALESCE(paid.total_paid, 0)), 0) as total_paid,
                   COALESCE(SUM(GREATEST(COALESCE(c.contract_value, 0) - COALESCE(paid.total_paid, 0), 0)), 0) as debt_total,
                   SUM(CASE WHEN {$problemWhere} THEN 1 ELSE 0 END) as problem_count,
                   SUM(CASE WHEN {$noProblemWhere} THEN 1 ELSE 0 END) as no_problem_count
            FROM apz_contracts c
            {$paidJoinSql}
            {$districtWhereSql}
            GROUP BY c.district
            ORDER BY debt_total DESC, contract_value DESC
        ");

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
            ]);
        }

        $topDebtConditions = array_merge($baseWhere, ['(COALESCE(c.contract_value, 0) > COALESCE(paid.total_paid, 0))']);
        $topDebtWhereSql = $topDebtConditions ? 'WHERE ' . implode(' AND ', $topDebtConditions) : '';

        $monitoringTopDebts = DB::select("
            SELECT c.contract_id, c.investor_name, c.district, c.contract_number, c.contract_date, c.phone,
                   c.contract_status, c.construction_issues,
                   COALESCE(c.contract_value, 0) as contract_value,
                   COALESCE(paid.total_paid, 0) as total_paid,
                   GREATEST(COALESCE(c.contract_value, 0) - COALESCE(paid.total_paid, 0), 0) as debt_total
            FROM apz_contracts c
            {$paidJoinSql}
            {$topDebtWhereSql}
            ORDER BY debt_total DESC
            LIMIT 32
        ");

        foreach ($monitoringTopDebts as $row) {
            $row->issue_label = $this->issueStatusLabel($row->construction_issues ?? null);
        }

        $newContractConditions = array_merge($baseWhere, [
            'c.contract_date IS NOT NULL',
            "c.contract_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
            "c.contract_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)",
        ]);
        $newContractWhereSql = 'WHERE ' . implode(' AND ', $newContractConditions);

        $monitoringNewContracts = DB::select("
            SELECT DATE(c.contract_date) as contract_day,
                   COUNT(*) as contracts_count,
                   COALESCE(SUM(c.contract_value), 0) as contract_value,
                   COALESCE(SUM(GREATEST(COALESCE(c.contract_value, 0) - COALESCE(paid.total_paid, 0), 0)), 0) as debt_total
            FROM apz_contracts c
            {$paidJoinSql}
            {$newContractWhereSql}
            GROUP BY DATE(c.contract_date)
            ORDER BY DATE(c.contract_date)
        ");

        if (empty($monitoringNewContracts)) {
            $fallbackConditions = array_merge($baseWhere, [
                'c.contract_date IS NOT NULL',
                'c.contract_date >= DATE_SUB(CURDATE(), INTERVAL 31 DAY)',
            ]);
            $fallbackWhereSql = 'WHERE ' . implode(' AND ', $fallbackConditions);

            $monitoringNewContracts = DB::select("
                SELECT DATE(c.contract_date) as contract_day,
                       COUNT(*) as contracts_count,
                       COALESCE(SUM(c.contract_value), 0) as contract_value,
                       COALESCE(SUM(GREATEST(COALESCE(c.contract_value, 0) - COALESCE(paid.total_paid, 0), 0)), 0) as debt_total
                FROM apz_contracts c
                {$paidJoinSql}
                {$fallbackWhereSql}
                GROUP BY DATE(c.contract_date)
                ORDER BY DATE(c.contract_date)
            ");
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
                SUM(CASE WHEN p.type='АПЗ тўлови'            AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as apz_payment,
                SUM(CASE WHEN p.type='Пеня тўлови'           AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as penalty,
                SUM(CASE WHEN p.type='АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END)/1000000 as refund,
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as total_income
            FROM apz_payments p
            WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
            GROUP BY p.district
            ORDER BY total_income DESC
        ");

        // ── 2. Plan per district (deduplicated by contract) ──────────────────
        $planFactRows = DB::select("
            SELECT p.district,
                SUM(c_plan.plan)/1000000 as plan_value,
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as fact_paid
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
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as income,
                SUM(CASE WHEN p.type='АПЗ тўлови'            AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as apz,
                SUM(CASE WHEN p.type='Пеня тўлови'           AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as pen,
                SUM(CASE WHEN p.type='АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END)/1000000 as ref,
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
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as fact_paid
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
                SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as fact_paid
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
                    + round($amountSom / 1000000, 4);
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
