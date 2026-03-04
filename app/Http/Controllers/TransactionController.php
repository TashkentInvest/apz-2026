<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
        $viewData = Cache::remember('apz_dashboard_data', self::CACHE_REPORT, function () {

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
            $contractStats = DB::selectOne("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN contract_status = 'Yakunlagan' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN contract_status = 'Bekor qilingan' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN (contract_status IS NULL OR contract_status = '') THEN 1 ELSE 0 END) as active,
                    SUM(contract_value) as total_value
                FROM apz_contracts
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

            return compact(
                'global', 'contractStats',
                'monthlyStats', 'districtStats', 'typeStats', 'planFact'
            );
        });

        return view('transactions.dashboard', $viewData);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SUMMARY — APZ payments by district and type  (Свод)
    // ──────────────────────────────────────────────────────────────────────
    public function summary(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : null;

        $cacheKey = 'apz_summary_report_' . ($year ?? 'all');

        $viewData = Cache::remember($cacheKey, self::CACHE_REPORT, function () use ($year) {
            $yearCond = $year ? 'AND p.year = ' . $year : '';

            // ── 1. Payments by district (Cyrillic names from fakt-apz) ─────────
            $payRows = DB::select("
                SELECT
                    p.district,
                    COUNT(DISTINCT p.contract_id)                                                    as contract_count,
                    SUM(CASE WHEN p.type = 'АПЗ тўлови'            AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as apz_payment,
                    SUM(CASE WHEN p.type = 'Пеня тўлови'           AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as penalty,
                    SUM(CASE WHEN p.type = 'АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END)/1000000 as refund,
                    SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000                  as total_income
                FROM apz_payments p
                WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
                GROUP BY p.district
                ORDER BY total_income DESC
            ");

            // ── 2. Plan vs Fact ────────────────────────────────────────────
            //   • Use MAX(contract_value) per contract_id first to avoid
            //     inflating plan when one contract has many payment rows.
            //   • Group by p.district (Cyrillic) so keys match payment data.
            $planFactRows = DB::select("
                SELECT
                    p.district,
                    SUM(c_plan.plan) / 1000000                              as plan_value,
                    SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as fact_paid
                FROM apz_payments p
                INNER JOIN (
                    SELECT contract_id, MAX(contract_value) as plan
                    FROM apz_contracts GROUP BY contract_id
                ) c_plan ON c_plan.contract_id = p.contract_id
                WHERE p.district IS NOT NULL AND p.district != ''
                GROUP BY p.district
                ORDER BY p.district
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

            // ── 3. Build summaryData + totals ──────────────────────────────────
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
                $totals['apz_payment']   += (float) $r->apz_payment;
                $totals['penalty']       += (float) $r->penalty;
                $totals['refund']        += (float) $r->refund;
                $totals['total_income']  += (float) $r->total_income;
                $totals['contract_count']+= (int)   $r->contract_count;
            }

            // ── 4. Year → Month → Day breakdown (for drill-down) ──────────────
            $drillRows = DB::select("
                SELECT
                    p.year,
                    p.month,
                    DATE(p.payment_date)                                                             as pay_day,
                    p.district,
                    SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000                  as income,
                    SUM(CASE WHEN p.type='АПЗ тўлови'            AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as apz,
                    SUM(CASE WHEN p.type='Пеня тўлови'           AND p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as pen,
                    SUM(CASE WHEN p.type='АПЗ тўловини қайтариш' AND p.flow='Расход' THEN p.amount ELSE 0 END)/1000000 as ref,
                    COUNT(DISTINCT p.contract_id)                                                    as cnt
                FROM apz_payments p
                WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
                GROUP BY p.year, p.month, pay_day, p.district
                ORDER BY p.year, MIN(p.payment_date), p.district
            ");

            // Organise: drill[year][month][day][district] = row
            $drill = [];
            $monthOrder = ['Январь'=>1,'Февраль'=>2,'Март'=>3,'Апрель'=>4,'Май'=>5,'Июнь'=>6,
                           'Июль'=>7,'Август'=>8,'Сентябрь'=>9,'Октябрь'=>10,'Ноябрь'=>11,'Декабрь'=>12];
            foreach ($drillRows as $r) {
                $drill[$r->year][$r->month][$r->pay_day][$r->district] = $r;
            }

            // ── 5. Fact totals by year+district and year+month+district ─────────
            //    (plan is fixed per district; fact sliced by time period)
            $pfYearRows = DB::select("
                SELECT p.year, p.district,
                    SUM(CASE WHEN p.flow='Приход' THEN p.amount ELSE 0 END)/1000000 as fact_paid
                FROM apz_payments p
                WHERE p.district IS NOT NULL AND p.district != '' {$yearCond}
                GROUP BY p.year, p.district
            ");
            $pfYear = []; // pfYear[year][district]
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
            $pfMonth = []; // pfMonth[year][month][district]
            foreach ($pfMonthRows as $r) {
                $pfMonth[$r->year][$r->month][$r->district] = (float) $r->fact_paid;
            }

            // ── 6. Parse payment_schedule → planSchedule[district][YYYY-MM-DD] += amount ──
            //    Maps planned due-dates (from grafik_apz) per district.
            //    We join via contract_id so district key = Cyrillic p.district.
            $contracts = DB::table('apz_contracts')
                ->whereNotNull('payment_schedule')
                ->where('payment_schedule', '!=', 'null')
                ->where('payment_schedule', '!=', '{}')
                ->where('payment_schedule', '!=', '[]')
                ->get(['contract_id', 'payment_schedule']);

            // Build district map: contract_id => Cyrillic district (from payments)
            $contractDistrict = DB::table('apz_payments')
                ->whereNotNull('district')->where('district', '!=', '')
                ->groupBy('contract_id')
                ->selectRaw('contract_id, MAX(district) as district')
                ->pluck('district', 'contract_id')
                ->toArray();

            // Russian month names lookup (for planSchedule month key)
            $ruMonths = [1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',
                         5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',
                         9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь'];

            // planSchedule[district][year][month][YYYY-MM-DD] += mln
            $planSchedule = [];
            foreach ($contracts as $c) {
                $sched = json_decode($c->payment_schedule, true);
                if (!is_array($sched) || empty($sched)) continue;
                $dist = $contractDistrict[$c->contract_id] ?? null;
                if (!$dist) continue;
                foreach ($sched as $rawDate => $amountSom) {
                    // Normalize date: "7/31/2024" or "2024-07-31" -> Carbon
                    try {
                        $dt = \Carbon\Carbon::parse($rawDate);
                    } catch (\Exception $e) {
                        continue;
                    }
                    $yy  = $dt->year;
                    $mm  = $ruMonths[$dt->month];
                    $day = $dt->format('Y-m-d');
                    $planSchedule[$dist][$yy][$mm][$day] = ($planSchedule[$dist][$yy][$mm][$day] ?? 0) + $amountSom / 1000000;
                }
            }

            // ── 7. Available years ─────────────────────────────────────────────
            $availableYears = DB::table('apz_payments')
                ->selectRaw('DISTINCT year')->whereNotNull('year')->where('year','>',0)
                ->orderBy('year','desc')->pluck('year')->toArray();

            return compact('summaryData','totals','planFact','availableYears','drill','monthOrder','pfYear','pfMonth','planSchedule');
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
        $status   = $request->filled('status')   ? $request->status   : null;

        $cacheKey = 'apz_summary2_' . md5(($district ?? 'all') . '|' . ($status ?? 'all'));

        $viewData = Cache::remember($cacheKey, self::CACHE_REPORT, function () use ($district, $status) {

            $cWhere = [];
            if ($district) $cWhere[] = "c.district = " . DB::getPdo()->quote($district);
            if ($status === 'active')    $cWhere[] = "(c.contract_status IS NULL OR c.contract_status = '')";
            if ($status === 'completed') $cWhere[] = "c.contract_status = 'Yakunlagan'";
            if ($status === 'cancelled') $cWhere[] = "c.contract_status = 'Bekor qilingan'";
            $cSQL = $cWhere ? 'WHERE ' . implode(' AND ', $cWhere) : '';

            // All contracts (filtered)
            $contracts = DB::select("
                SELECT c.id, c.contract_id, c.district, c.investor_name, c.inn,
                       c.contract_number, c.contract_date, c.contract_status,
                       c.contract_value, c.payment_terms, c.installments_count,
                       c.payment_schedule,
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

            // Totals
            $grandPlan = $grandFact = 0;
            foreach ($contracts as $c) {
                $grandPlan += (float) $c->contract_value;
                $grandFact += (float) $c->total_paid;
            }

            // Available districts & statuses for filter
            $availableDistricts = DB::table('apz_contracts')
                ->selectRaw('DISTINCT district')
                ->whereNotNull('district')->where('district', '!=', '')
                ->orderBy('district')
                ->pluck('district')
                ->toArray();

            return compact('contracts', 'grandPlan', 'grandFact', 'availableDistricts');
        });

        $viewData['selectedDistrict'] = $district;
        $viewData['selectedStatus']   = $status;
        return view('transactions.summary2', $viewData);
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
            'apz_filters', 'apz_summary', 'apz_dashboard_data',
            'apz_summary_report_all',
            'apz_summary2_' . md5('all|all'),
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

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Cache warmed']);
        }
        return redirect()->route('admin.dashboard')
            ->with('cache_cleared', 'Kesh qayta qurildi.');
    }
}
