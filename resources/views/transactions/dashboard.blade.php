@extends('layouts.app')

@section('title', 'АПЗ Бош панел')

@push('styles')
<style>
    /* ── Shared table block ── */
    .tbl-block {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e8e8e8;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .tbl-block-header {
        padding: 14px 20px;
        border-bottom: 1px solid #e8e8e8;
        font-size: 0.95rem;
        font-weight: 600;
        color: #15191e;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .tbl-block-header .sub {
        font-size: 0.75rem;
        font-weight: 400;
        color: #6e788b;
    }
    /* ── Stat cards ── */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e8e8e8;
        position: relative;
        overflow: hidden;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 4px; height: 100%;
        border-radius: 0 12px 12px 0;
    }
    .stat-card.teal::after   { background: #018c87; }
    .stat-card.blue::after   { background: #1471f0; }
    .stat-card.green::after  { background: #0bc33f; }
    .stat-card.orange::after { background: #f59e0b; }
    .stat-card.red::after    { background: #e63260; }

    .stat-card .sc-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #6e788b;
        margin-bottom: 8px;
    }
    .stat-card .sc-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #15191e;
        line-height: 1.1;
    }
    .stat-card .sc-sub {
        font-size: 0.75rem;
        color: #aab0bb;
        margin-top: 6px;
    }
    .stat-card .sc-delta {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.72rem;
        font-weight: 600;
        margin-top: 6px;
        padding: 2px 8px;
        border-radius: 20px;
    }
    .delta-up   { background: #d4f8e8; color: #0bc33f; }
    .delta-down { background: #fde8ef; color: #e63260; }

    /* ── Two-column grid ── */
    .two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    @media (max-width: 900px) {
        .two-col { grid-template-columns: 1fr; }
        .stats-row { grid-template-columns: 1fr 1fr; }
    }

    /* ── Inline table ── */
    .inline-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .inline-table thead th {
        padding: 11px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 0.78rem;
        color: #6e788b;
        text-transform: uppercase;
        letter-spacing: .05em;
        border-bottom: 1px solid #f0f2f5;
        background: #fafafa;
        white-space: nowrap;
    }
    .inline-table thead th.num { text-align: right; }
    .inline-table tbody tr { border-bottom: 1px solid #f5f5f5; transition: background .1s; }
    .inline-table tbody tr:hover { background: #f7f9fa; }
    .inline-table tbody td { padding: 12px 16px; vertical-align: middle; }
    .inline-table tbody td.num { text-align: right; font-weight: 600; color: #27314b; }
    .inline-table tbody td.cnt { text-align: right; color: #6e788b; font-size: 0.8rem; }
    .inline-table tbody td.name { font-weight: 500; color: #15191e; }

    /* ── Clickable row ── */
    .inline-table tbody tr.clickable { cursor:pointer; }
    .inline-table tbody tr.clickable:hover td { background:#d8f3f2 !important; }

    /* ── Detail modal ── */
    #detail-modal {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
        z-index:1000; align-items:center; justify-content:center; padding:16px;
    }
    #detail-modal.open { display:flex; }
    .dm-box {
        background:#fff; border-radius:14px; width:100%; max-width:900px;
        max-height:90vh; display:flex; flex-direction:column;
        box-shadow:0 8px 32px rgba(0,0,0,.18);
    }
    .dm-head {
        padding:16px 20px; border-bottom:1px solid #e8e8e8;
        display:flex; align-items:center; justify-content:space-between;
        background:#018c87; border-radius:14px 14px 0 0; color:#fff;
    }
    .dm-head h3 { margin:0; font-size:.95rem; font-weight:700; }
    .dm-head button { background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer; opacity:.8; }
    .dm-head button:hover { opacity:1; }
    .dm-body { padding:16px 20px; overflow-y:auto; flex:1; }
    .dm-table { width:100%; border-collapse:collapse; font-size:.8rem; }
    .dm-table th { padding:8px 10px; background:#f4fefe; color:#015c58; font-weight:600;
        border:1px solid #e0e0e0; text-align:center; white-space:nowrap; }
    .dm-table td { padding:7px 10px; border:1px solid #ebebeb; color:#27314b; }
    .dm-table td.r { text-align:right; }
    .dm-table tr:nth-child(even) td { background:#f9fefe; }
    .dm-table tr:hover td { background:#e8f7f6 !important; }
    .dm-footer { padding:12px 20px; border-top:1px solid #e8e8e8;
        display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
    .dm-pg-btn {
        padding:5px 14px; border:1px solid #018c87; border-radius:6px;
        background:#fff; color:#018c87; font-size:.78rem; font-weight:600; cursor:pointer;
    }
    .dm-pg-btn:hover { background:#018c87; color:#fff; }
    .dm-pg-btn:disabled { opacity:.4; cursor:not-allowed; }
    .dm-pg-info { font-size:.78rem; color:#6e788b; }
    .flow-in  { color:#0a8a2e; font-weight:600; }
    .flow-out { color:#e63260; font-weight:600; }
    .dm-contract-btn {
        padding:3px 10px; border:1px solid #018c87; border-radius:5px;
        background:#fff; color:#018c87; font-size:.72rem; font-weight:600;
        cursor:pointer; white-space:nowrap;
    }
    .dm-contract-btn:hover { background:#018c87; color:#fff; }

    /* ── Contract detail modal (shared with summary2) ── */
    #contract-modal {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
        z-index:1100; align-items:center; justify-content:center; padding:16px;
    }
    #contract-modal.open { display:flex; }
    .cm-box {
        background:#fff; border-radius:14px; width:100%; max-width:860px;
        max-height:92vh; display:flex; flex-direction:column;
        box-shadow:0 8px 32px rgba(0,0,0,.2);
    }
    .cm-head {
        padding:14px 20px; background:#018c87; border-radius:14px 14px 0 0; color:#fff;
        display:flex; align-items:center; justify-content:space-between;
    }
    .cm-head h3 { margin:0; font-size:.92rem; font-weight:700; }
    .cm-head button { background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer; }
    .cm-body { padding:18px 20px; overflow-y:auto; flex:1; }
    .cm-meta { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:10px; margin-bottom:18px; }
    .cm-meta-item { background:#f4fefe; border-radius:8px; padding:10px 14px; border:1px solid #d3f0ee; }
    .cm-meta-item .lbl { font-size:.68rem; color:#6e788b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
    .cm-meta-item .val { font-size:.88rem; font-weight:600; color:#15191e; }
    .cm-section-title { font-size:.78rem; font-weight:700; color:#018c87; text-transform:uppercase;
        letter-spacing:.05em; margin:16px 0 8px; border-bottom:1px solid #e0e0e0; padding-bottom:6px; }
    .cm-table { width:100%; border-collapse:collapse; font-size:.79rem; margin-bottom:10px; }
    .cm-table th { padding:7px 10px; background:#f4fefe; color:#015c58; font-weight:600; border:1px solid #e0e0e0; text-align:center; }
    .cm-table td { padding:6px 10px; border:1px solid #ebebeb; }
    .cm-table td.r { text-align:right; }
    .cm-table tr:nth-child(even) td { background:#f9fdfd; }
    .cm-footer { padding:12px 20px; border-top:1px solid #e8e8e8;
        display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
    .cm-pg-btn { padding:5px 14px; border:1px solid #018c87; border-radius:6px;
        background:#fff; color:#018c87; font-size:.78rem; font-weight:600; cursor:pointer; }
    .cm-pg-btn:hover { background:#018c87; color:#fff; }
    .cm-pg-btn:disabled { opacity:.4; cursor:not-allowed; }

    /* ── Month badge ── */
    .month-badge {
        display: inline-block;
        background: #f0f9f8;
        color: #018c87;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        border: 1px solid #b2e4e1;
    }

    /* ── Drill-down svod table (from summary) ── */
    .svod-table { width:100%; border-collapse:collapse; font-size:.83rem; }
    .svod-table thead tr.hdr-group th {
        padding:7px 10px; font-weight:700; font-size:.71rem;
        color:#fff; border:1px solid rgba(255,255,255,.2);
        text-align:center; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap;
    }
    .svod-table thead tr.hdr-cols th {
        padding:9px 10px; font-weight:600; font-size:.72rem;
        color:#fff; background:#018c87; border:1px solid #017570;
        text-align:center; vertical-align:middle; line-height:1.3; white-space:nowrap;
    }
    .svod-table thead tr.hdr-cols th:first-child { text-align:left; }
    .svod-table thead tr.hdr-group  th.g-pay  { background:#018c87; }
    .svod-table thead tr.hdr-group  th.g-pf   { background:#1d6954; }
    .svod-table thead tr.hdr-cols  th.g-pf   { background:#1d6954; border-color:#175945; }
    .svod-table tbody td {
        padding:7px 10px; border:1px solid #ebebeb; color:#27314b; vertical-align:middle;
    }
    .svod-table tbody tr { transition:background .1s; }
    .svod-table tbody tr:hover { background:#e8f7f6 !important; }
    .svod-table tbody td.num  { text-align:right; font-weight:500; }
    .svod-table tbody td.sep  { border-left:1px solid #a8d8d5 !important; }
    .row-district td    { background:#f4fefe; font-weight:600; }
    .row-district .d-name { display:flex; align-items:center; gap:6px; }
    .row-year td    { background:#edf8f7; font-weight:600; font-size:.8rem; }
    .row-year .d-name { padding-left:18px; display:flex; align-items:center; gap:6px; }
    .row-month td   { background:#f5fbfb; font-size:.78rem; }
    .row-month .d-name { padding-left:36px; display:flex; align-items:center; gap:6px; }
    .row-day td     { background:#fff; font-size:.76rem; color:#444; padding:5px 10px; }
    .row-day .d-name { padding-left:52px; display:flex; align-items:center; gap:6px; }
    .row-total td   {
        background:#e8f4f3; font-weight:700; color:#015c58;
        border-top:2px solid #018c87; border-bottom:2px solid #018c87;
    }
    .tog {
        display:inline-flex; align-items:center; justify-content:center;
        width:18px; height:18px; border-radius:4px; font-size:.8rem; font-weight:700;
        cursor:pointer; user-select:none; flex-shrink:0;
        background:#018c87; color:#fff; border:none; line-height:1;
        transition:background .15s;
    }
    .tog:hover { background:#017570; }
    .tog.collapsed::after { content:'+'; }
    .tog.expanded::after  { content:'−'; }
    .pbar-wrap { background:#e4e4e4; border-radius:3px; height:7px; min-width:60px; }
    .pbar      { height:7px; border-radius:3px; background:#018c87; }
    .pbar.over { background:#e63260; }
    .row-day--plan-only td:first-child { border-left:3px solid #f0a500 !important; }
    .row-day--fact-only td:first-child { border-left:3px solid #1aad4e !important; }
    .row-day--both      td:first-child { border-left:3px solid #018c87 !important; }
    .day-badge {
        display:inline-flex; align-items:center;
        font-size:.6rem; font-weight:700;
        padding:1px 5px; border-radius:10px; letter-spacing:.03em;
        text-transform:uppercase; white-space:nowrap; line-height:1.4;
        vertical-align:middle; margin-left:4px;
    }
    .day-badge--plan  { background:#fff0b3; color:#7a5900; border:1px solid #f0a500; }
    .day-badge--fact  { background:#d6f5e3; color:#0d6e30; border:1px solid #1aad4e; }
    .day-badge--both  { background:#ccf0ee; color:#015c58; border:1px solid #018c87; }
</style>
@endpush

@section('content')

@php
    $totalIncome    = $global->total_income ?? 0;
    $totalExpense   = $global->total_expense ?? 0;
    $totalRecords   = $global->total_records ?? 0;
    $uDistricts     = $global->unique_districts ?? 0;
    $uContracts     = $global->unique_contracts ?? 0;

    $totalContracts = $contractStats->total ?? 0;
    $completedC     = $contractStats->completed ?? 0;
    $cancelledC     = $contractStats->cancelled ?? 0;
    $activeC        = $contractStats->active ?? 0;
    $totalPlanValue = $contractStats->total_value ?? 0;

    $maxDistrict = (is_array($districtStats) && count($districtStats)) ? max(array_column($districtStats, 'income')) : 1;
    $maxDistrict = $maxDistrict ?: 1;
    $maxType     = (is_array($typeStats) && count($typeStats)) ? max(array_column($typeStats, 'income')) : 1;
    $maxType     = $maxType ?: 1;
@endphp

{{-- ── Top stat cards ── --}}
<div class="stats-row">
    <div class="stat-card teal">
        <div class="sc-label">Жами Приход (АПЗ)</div>
        <div class="sc-value">{{ number_format($totalIncome / 1000000, 1, '.', ' ') }} млн</div>
        <div class="sc-sub">сўм &middot; {{ number_format($totalRecords) }} та йозув</div>
    </div>
    <div class="stat-card blue">
        <div class="sc-label">Жами Шартномалар</div>
        <div class="sc-value">{{ number_format($totalContracts) }}</div>
        <div class="sc-sub">Фаол: {{ $activeC }} &middot; Якун: {{ $completedC }} &middot; Бекор: {{ $cancelledC }}</div>
    </div>
    <div class="stat-card green">
        <div class="sc-label">Шартнома умумий қиймати</div>
        <div class="sc-value">{{ number_format($totalPlanValue / 1000000, 1, '.', ' ') }} млн</div>
        <div class="sc-sub">сўм (режа-жадвал)</div>
    </div>
    <div class="stat-card orange">
        <div class="sc-label">Туманлар сони</div>
        <div class="sc-value">{{ $uDistricts }}</div>
        <div class="sc-sub">Уникал шартномалар: {{ $uContracts }}</div>
    </div>
    <div class="stat-card {{ $totalIncome >= $totalPlanValue ? 'teal' : 'red' }}">
        <div class="sc-label">Умумий бажарилиш</div>
        <div class="sc-value">{{ $totalPlanValue > 0 ? number_format($totalIncome / $totalPlanValue * 100, 1) : 0 }}%</div>
        <div class="sc-sub">факт / режа</div>
    </div>
</div>

{{-- ── Drill-down: Туман → Йил → Ой → Кун ── --}}
@php
    $grandPlan = array_sum(array_column($planFact, 'plan'));
    $grandFact = array_sum(array_column($planFact, 'fact'));
    $grandBal  = $grandPlan - $grandFact;
    $grandPct  = $grandPlan > 0 ? round($grandFact / $grandPlan * 100, 1) : 0;
    $grandBarW = min($grandPct, 100);
@endphp
<div class="tbl-block">
    <div class="tbl-block-header">
        <span class="title">Туман &rarr; Йил &rarr; Ой &rarr; Кун (дрилл-даун)</span>
        <span class="sub">млн.сўм &middot; + босиб кенгайтиринг</span>
        <div style="display:flex;gap:8px;align-items:center;" class="no-print">
            <button onclick="expandAll()" class="print-btn" style="background:#6e788b;padding:6px 14px;font-size:.75rem;">+ Барчасини очиш</button>
            <button onclick="collapseAll()" class="print-btn" style="background:#6e788b;padding:6px 14px;font-size:.75rem;">− Барчасини юмиш</button>
        </div>
    </div>
    <div style="overflow-x:auto;">
    <table class="svod-table" id="svod-tbl">
        <thead>
            <tr class="hdr-group">
                <th rowspan="2" class="g-pay" style="text-align:left;min-width:160px;">Туман / Йил / Ой</th>
                <th colspan="5" class="g-pay">АПЗ Тўловлари (факт, млн.сўм)</th>
                <th colspan="5" class="g-pf">План — Факт</th>
            </tr>
            <tr class="hdr-cols">
                <th class="g-pay">Шартн.</th>
                <th class="g-pay">Жами тушум</th>
                <th class="g-pay">АПЗ тўлови</th>
                <th class="g-pay">Пеня</th>
                <th class="g-pay">Қайтариш</th>
                <th class="g-pf sep">План</th>
                <th class="g-pf">Факт</th>
                <th class="g-pf">Қолдиқ</th>
                <th class="g-pf">%</th>
                <th class="g-pf">Прогресс</th>
            </tr>
        </thead>
        <tbody>
            {{-- GRAND TOTAL row --}}
            <tr class="row-total">
                <td style="font-weight:700;">ЖАМИ</td>
                <td class="num">{{ number_format($totals['contract_count']) }}</td>
                <td class="num">{{ number_format($totals['total_income'], 2, '.', ' ') }}</td>
                <td class="num">{{ number_format($totals['apz_payment'], 2, '.', ' ') }}</td>
                <td class="num">{{ $totals['penalty'] > 0 ? number_format($totals['penalty'], 2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $totals['refund']  > 0 ? number_format($totals['refund'],  2, '.', ' ') : '—' }}</td>
                <td class="num sep">{{ number_format($grandPlan, 2, '.', ' ') }}</td>
                <td class="num" style="color:#0a8a2e;">{{ number_format($grandFact, 2, '.', ' ') }}</td>
                <td class="num" style="color:{{ $grandBal <= 0 ? '#0bc33f' : '#e63260' }};">
                    {{ number_format($grandBal, 2, '.', ' ') }}
                </td>
                <td class="num" style="font-weight:700;color:{{ $grandPct >= 100 ? '#0bc33f' : '#27314b' }};">
                    {{ number_format($grandPct, 1) }}%
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:5px;">
                        <div class="pbar-wrap" style="flex:1;"><div class="pbar {{ $grandPct > 100 ? 'over' : '' }}" style="width:{{ $grandBarW }}%;"></div></div>
                        <span style="font-size:.7rem;color:#6e788b;">{{ number_format($grandPct, 1) }}%</span>
                    </div>
                </td>
            </tr>

            {{-- Per district rows --}}
            @foreach($summaryData as $row)
            @php
                $dist    = $row['district'];
                $pf      = $planFact[$dist] ?? ['plan'=>0,'fact'=>0,'pct'=>0,'balance'=>0];
                $isOver  = $pf['pct'] > 100;
                $barW    = min($pf['pct'], 100);
                $dKey    = 'd_' . md5($dist);
                $distYears = [];
                foreach ($dayRows[$dist] ?? [] as $yr => $_) { $distYears[$yr] = true; }
                ksort($distYears);
            @endphp

            {{-- District row --}}
            <tr class="row-district" data-level="district" data-key="{{ $dKey }}">
                <td>
                    <div class="d-name">
                        @if(count($distYears))
                        <button class="tog collapsed" onclick="toggleGroup('{{ $dKey }}',this)" title="Очиш / Юмиш"></button>
                        @endif
                        {{ $dist }}
                    </div>
                </td>
                <td class="num">{{ $row['contract_count'] }}</td>
                <td class="num">{{ number_format($row['total_income'], 2, '.', ' ') }}</td>
                <td class="num">{{ $row['apz_payment'] > 0 ? number_format($row['apz_payment'], 2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $row['penalty']     > 0 ? number_format($row['penalty'],     2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $row['refund']      > 0 ? number_format($row['refund'],      2, '.', ' ') : '—' }}</td>
                <td class="num sep">{{ $pf['plan'] > 0 ? number_format($pf['plan'], 2, '.', ' ') : '—' }}</td>
                <td class="num" style="color:#0a8a2e;">{{ $pf['fact'] > 0 ? number_format($pf['fact'], 2, '.', ' ') : '—' }}</td>
                <td class="num" style="color:{{ $pf['balance'] <= 0 ? '#0bc33f' : '#e63260' }};font-weight:600;">
                    {{ $pf['plan'] > 0 ? number_format($pf['balance'], 2, '.', ' ') : '—' }}
                </td>
                <td class="num" style="font-weight:700;color:{{ $isOver ? '#0bc33f' : '#27314b' }};">
                    {{ $pf['plan'] > 0 ? number_format($pf['pct'], 1).'%' : '—' }}
                </td>
                <td>
                    @if($pf['plan'] > 0)
                    <div style="display:flex;align-items:center;gap:5px;">
                        <div class="pbar-wrap" style="flex:1;"><div class="pbar {{ $isOver ? 'over' : '' }}" style="width:{{ $barW }}%;"></div></div>
                        <span style="font-size:.7rem;color:#6e788b;">{{ number_format($pf['pct'], 1) }}%</span>
                    </div>
                    @else <span style="color:#bbb;">—</span>
                    @endif
                </td>
            </tr>

            {{-- Year level --}}
            @foreach($distYears as $yr => $_)
            @php
                $yrKey    = $dKey . '_y' . $yr;
                $yrFact   = $pfYear[$yr][$dist] ?? 0;
                $yrPlan   = $pfYearPlan[$dist][$yr] ?? 0;
                $yrBal    = $yrPlan > 0 ? round($yrPlan - $yrFact, 2) : null;
                $yrPct    = $yrPlan > 0 ? round($yrFact / $yrPlan * 100, 1) : null;
                $yrBarW   = $yrPct !== null ? min($yrPct, 100) : 0;
                $yrIsOver = $yrPct !== null && $yrPct > 100;
                $yrIncome = $yrApz = $yrPen = $yrRef = $yrCnt = 0;
                foreach ($dayRows[$dist][$yr] ?? [] as $mo => $dRows) {
                    foreach ($dRows as $dr) {
                        $yrIncome += $dr['income'] ?? 0;
                        $yrApz    += $dr['apz']    ?? 0;
                        $yrPen    += $dr['pen']    ?? 0;
                        $yrRef    += $dr['ref']    ?? 0;
                        $yrCnt    += $dr['cnt']    ?? 0;
                    }
                }
            @endphp
            <tr class="row-year" data-parent="{{ $dKey }}" data-key="{{ $yrKey }}" style="display:none;">
                <td>
                    <div class="d-name">
                        <button class="tog collapsed" onclick="toggleGroup('{{ $yrKey }}',this)"></button>
                        <strong>{{ $yr }} йил</strong>
                    </div>
                </td>
                <td class="num">{{ $yrCnt ?: '—' }}</td>
                <td class="num">{{ $yrIncome > 0 ? number_format($yrIncome, 2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $yrApz   > 0 ? number_format($yrApz,    2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $yrPen   > 0 ? number_format($yrPen,    2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $yrRef   > 0 ? number_format($yrRef,    2, '.', ' ') : '—' }}</td>
                <td class="num sep" style="color:#6e788b;">{{ $yrPlan > 0 ? number_format($yrPlan, 2, '.', ' ') : '—' }}</td>
                <td class="num" style="color:#0a8a2e;">{{ $yrFact > 0 ? number_format($yrFact, 2, '.', ' ') : '—' }}</td>
                <td class="num" style="color:{{ $yrBal !== null ? ($yrBal <= 0 ? '#0bc33f' : '#e63260') : '' }};">
                    {{ $yrBal !== null ? number_format($yrBal, 2, '.', ' ') : '—' }}
                </td>
                <td class="num" style="color:{{ $yrIsOver ? '#0bc33f' : '#27314b' }};">
                    {{ $yrPct !== null ? number_format($yrPct, 1).'%' : '—' }}
                </td>
                <td>
                    @if($yrPct !== null)
                    <div style="display:flex;align-items:center;gap:4px;">
                        <div class="pbar-wrap" style="flex:1;"><div class="pbar {{ $yrIsOver ? 'over' : '' }}" style="width:{{ $yrBarW }}%;"></div></div>
                        <span style="font-size:.68rem;color:#6e788b;">{{ number_format($yrPct,1) }}%</span>
                    </div>
                    @else <span style="color:#bbb;">—</span>
                    @endif
                </td>
            </tr>

            {{-- Month level --}}
            @foreach($dayRows[$dist][$yr] ?? [] as $mo => $moDayRows)
            @php
                $moIdx = array_search($mo, ['Январь','Февраль','Март','Апрель','Май','Июнь',
                                            'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь']);
                $moKey    = $yrKey . '_m' . ($moIdx !== false ? ($moIdx + 1) : md5($mo));
                $moFact   = $pfMonth[$yr][$mo][$dist] ?? 0;
                $moPlan   = $pfMonthPlan[$dist][$yr][$mo] ?? 0;
                $moBal    = $moPlan > 0 ? round($moPlan - $moFact, 2) : null;
                $moPct    = $moPlan > 0 ? round($moFact / $moPlan * 100, 1) : null;
                $moBarW   = $moPct !== null ? min($moPct, 100) : 0;
                $moIsOver = $moPct !== null && $moPct > 100;
                $moIncome = $moApz = $moPen = $moRef = $moCnt = 0;
                foreach ($moDayRows as $dr) {
                    $moIncome += $dr['income'] ?? 0;
                    $moApz    += $dr['apz']    ?? 0;
                    $moPen    += $dr['pen']    ?? 0;
                    $moRef    += $dr['ref']    ?? 0;
                    $moCnt    += $dr['cnt']    ?? 0;
                }
            @endphp
            <tr class="row-month" data-parent="{{ $yrKey }}" data-key="{{ $moKey }}" style="display:none;">
                <td>
                    <div class="d-name">
                        <button class="tog collapsed" onclick="toggleGroup('{{ $moKey }}',this)"></button>
                        {{ $mo }}
                    </div>
                </td>
                <td class="num">{{ $moCnt ?: '—' }}</td>
                <td class="num">{{ $moIncome > 0 ? number_format($moIncome, 2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $moApz   > 0 ? number_format($moApz,    2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $moPen   > 0 ? number_format($moPen,    2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $moRef   > 0 ? number_format($moRef,    2, '.', ' ') : '—' }}</td>
                <td class="num sep" style="color:#6e788b;">{{ $moPlan > 0 ? number_format($moPlan, 2, '.', ' ') : '—' }}</td>
                <td class="num" style="color:#0a8a2e;">{{ $moFact > 0 ? number_format($moFact, 2, '.', ' ') : '—' }}</td>
                <td class="num" style="color:{{ $moBal !== null ? ($moBal <= 0 ? '#0bc33f' : '#e63260') : '' }};">
                    {{ $moBal !== null ? number_format($moBal, 2, '.', ' ') : '—' }}
                </td>
                <td class="num" style="color:{{ $moIsOver ? '#0bc33f' : '#27314b' }};">
                    {{ $moPct !== null ? number_format($moPct, 1).'%' : '—' }}
                </td>
                <td>
                    @if($moPct !== null)
                    <div style="display:flex;align-items:center;gap:4px;">
                        <div class="pbar-wrap" style="flex:1;"><div class="pbar {{ $moIsOver ? 'over' : '' }}" style="width:{{ $moBarW }}%;"></div></div>
                        <span style="font-size:.68rem;color:#6e788b;">{{ number_format($moPct,1) }}%</span>
                    </div>
                    @else <span style="color:#bbb;">—</span>
                    @endif
                </td>
            </tr>

            {{-- Day level --}}
            @foreach($moDayRows as $dr)
            <tr class="row-day row-day--{{ $dr['type'] }}" data-parent="{{ $moKey }}" style="display:none;">
                <td>
                    <div class="d-name">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#aab0bb" stroke-width="2.5"><circle cx="12" cy="12" r="4"/></svg>
                        <span style="font-weight:500;color:#27314b;">{{ $dr['date_fmt'] }}</span>
                        @if($dr['type'] === 'plan')
                            <span class="day-badge day-badge--plan">П</span>
                        @elseif($dr['type'] === 'fact')
                            <span class="day-badge day-badge--fact">Ф</span>
                        @else
                            <span class="day-badge day-badge--both">П+Ф</span>
                        @endif
                    </div>
                </td>
                <td class="num" style="color:#888;">{{ $dr['cnt'] ?: '—' }}</td>
                <td class="num">{{ $dr['income'] !== null ? number_format($dr['income'], 2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $dr['apz']    !== null ? number_format($dr['apz'],    2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $dr['pen']    !== null ? number_format($dr['pen'],    2, '.', ' ') : '—' }}</td>
                <td class="num">{{ $dr['ref']    !== null ? number_format($dr['ref'],    2, '.', ' ') : '—' }}</td>
                <td class="num sep" style="color:{{ $dr['plan'] !== null ? '#015c58' : '#ccc' }};">
                    {{ $dr['plan'] !== null ? number_format($dr['plan'], 2, '.', ' ') : '—' }}
                </td>
                <td class="num" style="color:{{ $dr['fact'] !== null ? '#0a8a2e' : '#bbb' }};">
                    {{ $dr['fact'] !== null ? number_format($dr['fact'], 2, '.', ' ') : '—' }}
                </td>
                <td class="num" style="color:{{ $dr['balance'] !== null ? ($dr['balance'] <= 0 ? '#0bc33f' : '#e63260') : '#bbb' }};">
                    {{ $dr['balance'] !== null ? number_format($dr['balance'], 2, '.', ' ') : '—' }}
                </td>
                <td class="num" style="color:{{ $dr['pct'] !== null && $dr['pct'] >= 100 ? '#0bc33f' : '#555' }};">
                    {{ $dr['pct'] !== null ? number_format($dr['pct'], 1).'%' : '—' }}
                </td>
                <td style="padding:5px 8px;">
                    @if($dr['type'] === 'both')
                    <div style="display:flex;align-items:center;gap:3px;">
                        <div class="pbar-wrap" style="flex:1;height:5px;"><div class="pbar {{ $dr['pct'] > 100 ? 'over' : '' }}" style="width:{{ $dr['bar_w'] }}%;height:5px;"></div></div>
                        <span style="font-size:.65rem;color:#6e788b;">{{ number_format($dr['pct'],1) }}%</span>
                    </div>
                    @elseif($dr['type'] === 'plan')
                        <span style="font-size:.68rem;color:#e63260;">— кутмади</span>
                    @else
                        <span style="font-size:.68rem;color:#0a8a2e;">✓ тўлов</span>
                    @endif
                </td>
            </tr>
            @endforeach {{-- /day rows --}}

            @endforeach {{-- /months --}}
            @endforeach {{-- /years --}}
            @endforeach {{-- /districts --}}

        </tbody>
    </table>
    </div>
</div>

{{-- ── Detail Modal ── --}}
<div id="detail-modal">
    <div class="dm-box">
        <div class="dm-head">
            <h3 id="dm-title">Тафсилот</h3>
            <button onclick="closeModal()">✕</button>
        </div>
        <div class="dm-body">
            <div style="overflow-x:auto;">
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Сана</th>
                            <th>Туман</th>
                            <th>Шартнома</th>
                            <th>Инвестор</th>
                            <th>Тур</th>
                            <th>Млн.сўм</th>
                            <th>Мақсад</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="dm-tbody">
                        <tr><td colspan="9" style="text-align:center;padding:30px;color:#aaa;">Юкланиш кутилмоқда...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dm-footer">
            <button id="dm-prev" class="dm-pg-btn" disabled>← Олдинги</button>
            <span id="dm-pg-info" class="dm-pg-info">—</span>
            <button id="dm-next" class="dm-pg-btn" disabled>Кейинги →</button>
        </div>
    </div>
</div>

@endsection

{{-- ── Contract Detail Modal ── --}}
<div id="contract-modal">
    <div class="cm-box">
        <div class="cm-head">
            <h3 id="cm-title">Шартнома тафсилоти</h3>
            <button onclick="closeContract()">&#x2715;</button>
        </div>
        <div class="cm-body">
            <div id="cm-meta" class="cm-meta"></div>
            <div class="cm-section-title">Тўлов жадвали</div>
            <div style="overflow-x:auto;">
                <table class="cm-table">
                    <thead><tr><th>#</th><th>Сана</th><th>Млн.сўм</th></tr></thead>
                    <tbody id="cm-sched-body"><tr><td colspan="3" style="text-align:center;color:#aaa;padding:12px;">Юкланмоқда...</td></tr></tbody>
                </table>
            </div>
            <div class="cm-section-title">Амалга тушумлар</div>
            <div style="overflow-x:auto;">
                <table class="cm-table">
                    <thead><tr><th>#</th><th>Сана</th><th>Тур</th><th>Оқим</th><th>Млн.сўм</th><th>Мақсад</th></tr></thead>
                    <tbody id="cm-pay-body"><tr><td colspan="6" style="text-align:center;color:#aaa;padding:12px;">Юкланмоқда...</td></tr></tbody>
                </table>
            </div>
        </div>
        <div class="cm-footer">
            <button id="cm-prev" class="cm-pg-btn" disabled>← Олдинги</button>
            <span id="cm-pg-info" style="font-size:.78rem;color:#6e788b;">—</span>
            <button id="cm-next" class="cm-pg-btn" disabled>Кейинги →</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const MODAL_URL = '{{ route('modal.payments') }}';
let modalState = { params:{}, page:1, lastPage:1, title:'' };

function openModal(title, params, page) {
    modalState = { params, page: page||1, lastPage:1, title };
    document.getElementById('dm-title').textContent = title;
    document.getElementById('detail-modal').classList.add('open');
    fetchModal();
}

function closeModal() {
    document.getElementById('detail-modal').classList.remove('open');
}

function fetchModal() {
    const body = document.getElementById('dm-tbody');
    const info = document.getElementById('dm-pg-info');
    body.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#aaa;">Юкланмоқда...</td></tr>';
    const params = new URLSearchParams({ ...modalState.params, page: modalState.page });
    fetch(MODAL_URL + '?' + params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            modalState.lastPage = data.last_page;
            renderModalRows(data);
            info.textContent = `${data.page} / ${data.last_page} (Жами: ${data.total})`;
            document.getElementById('dm-prev').disabled = data.page <= 1;
            document.getElementById('dm-next').disabled = data.page >= data.last_page;
        })
        .catch(() => { body.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#e63260;">Хатолик юз берди</td></tr>'; });
}

function renderModalRows(data) {
    const body = document.getElementById('dm-tbody');
    if (!data.rows.length) {
        body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px;color:#aaa;">Маълумот йўқ</td></tr>';
        return;
    }
    body.innerHTML = data.rows.map((r, i) => {
        const hasContract = r.contract_id ? true : false;
        const btnHtml = hasContract
            ? `<button class="dm-contract-btn" onclick="event.stopPropagation();openContract(${r.contract_id},'${(r.contract_number||'ID:'+r.contract_id).replace(/'/g,"\\'")}')">&#128196; Кўриш</button>`
            : '<span style="color:#ccc;font-size:.72rem;">—</span>';
        return `
        <tr>
            <td style="color:#aaa;font-size:.72rem;">${(data.page-1)*data.per_page + i + 1}</td>
            <td>${r.payment_date || '—'}</td>
            <td>${r.district || '—'}</td>
            <td style="font-size:.75rem;color:#018c87;">${r.contract_number || r.contract_id || '—'}</td>
            <td style="font-size:.76rem;">${r.investor_name || r.company_name || '—'}</td>
            <td style="font-size:.75rem;">${r.type || '—'}</td>
            <td class="r ${r.flow === 'Приход' ? 'flow-in' : 'flow-out'}">${r.flow === 'Приход' ? '+' : '-'}${Number(r.amount/1000000).toFixed(2)}</td>
            <td style="font-size:.72rem;color:#888;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r.payment_purpose || '—'}</td>
            <td style="text-align:center;">${btnHtml}</td>
        </tr>`;
    }).join('');
}

const CONTRACT_URL = '{{ url('/modal/contract') }}';
let cmState = { contractId: null, page: 1, lastPage: 1 };

function openContract(contractId, title) {
    cmState = { contractId, page: 1, lastPage: 1 };
    document.getElementById('cm-title').textContent = title;
    document.getElementById('contract-modal').classList.add('open');
    document.getElementById('cm-meta').innerHTML = '<div style="color:#aaa;text-align:center;padding:20px;">Юкланмоқда...</div>';
    document.getElementById('cm-sched-body').innerHTML = '<tr><td colspan="3" style="text-align:center;color:#aaa;padding:10px;">...</td></tr>';
    document.getElementById('cm-pay-body').innerHTML = '<tr><td colspan="6" style="text-align:center;color:#aaa;padding:10px;">...</td></tr>';
    fetchContract();
}
function closeContract() {
    document.getElementById('contract-modal').classList.remove('open');
}
function fetchContract() {
    const prev = document.getElementById('cm-prev');
    const next = document.getElementById('cm-next');
    const info = document.getElementById('cm-pg-info');
    prev.disabled = true; next.disabled = true;
    fetch(`${CONTRACT_URL}/${cmState.contractId}?page=${cmState.page}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { document.getElementById('cm-meta').innerHTML = `<div style="color:#e63260;padding:20px;">${data.error}</div>`; return; }
        cmState.lastPage = data.last_page;
        // meta
        const c = data.contract;
        const fmtM = v => v > 0 ? (v/1000000).toFixed(4)+' млн' : '—';
        const metaItems = [
            ['Шартнома рақами', c.contract_number||'—'],
            ['Туман', c.district||'—'],
            ['Инвестор', c.investor_name||'—'],
            ['Шартнома санаси', c.contract_date ? c.contract_date.slice(0,10).split('-').reverse().join('.') : '—'],
            ['Шартнома қиймати', fmtM(c.contract_value)],
            ['Жами тўланган', fmtM(c.total_paid)],
            ['Тўловлар сони', c.payment_count||'0'],
            ['Тўлов шарти', c.payment_terms||'—'],
            ['Ҳолат', c.contract_status||'—'],
        ];
        document.getElementById('cm-meta').innerHTML = metaItems.map(([l,v]) =>
            `<div class="cm-meta-item"><div class="lbl">${l}</div><div class="val">${v}</div></div>`).join('');
        // schedule
        const sBody = document.getElementById('cm-sched-body');
        sBody.innerHTML = data.schedule && data.schedule.length
            ? data.schedule.map((s,i) => `<tr><td style="text-align:center;color:#aaa;font-size:.72rem;">${i+1}</td><td>${s.date}</td><td class="r">${Number(s.amount).toFixed(4)}</td></tr>`).join('')
            : '<tr><td colspan="3" style="text-align:center;color:#aaa;padding:10px;">Жадвал йўқ</td></tr>';
        // payments
        const pBody = document.getElementById('cm-pay-body');
        pBody.innerHTML = data.payments && data.payments.length
            ? data.payments.map((p,i) => { const isIn = p.flow==='Приход'; return `<tr>
                <td style="text-align:center;color:#aaa;font-size:.72rem;">${(data.page-1)*data.per_page+i+1}</td>
                <td>${p.payment_date||'—'}</td>
                <td style="font-size:.75rem;">${p.type||'—'}</td>
                <td class="${isIn?'flow-in':'flow-out'}">${p.flow||'—'}</td>
                <td class="r ${isIn?'flow-in':'flow-out'}">${isIn?'+':'-'}${(p.amount/1000000).toFixed(4)}</td>
                <td style="font-size:.72rem;color:#888;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${p.payment_purpose||''}">${p.payment_purpose||'—'}</td>
            </tr>`; }).join('')
            : '<tr><td colspan="6" style="text-align:center;color:#aaa;padding:10px;">Тўловлар йўқ</td></tr>';
        info.textContent = `${data.page} / ${data.last_page} (Жами: ${data.total})`;
        prev.disabled = data.page <= 1;
        next.disabled = data.page >= data.last_page;
    })
    .catch(err => { document.getElementById('cm-meta').innerHTML = '<div style="color:#e63260;padding:20px;">Хатолик юз берди</div>'; console.error(err); });
}
document.getElementById('cm-prev').onclick = () => { if(cmState.page>1){cmState.page--;fetchContract();} };
document.getElementById('cm-next').onclick = () => { if(cmState.page<cmState.lastPage){cmState.page++;fetchContract();} };
document.getElementById('contract-modal').addEventListener('click', e => { if(e.target===e.currentTarget) closeContract(); });

// Drill-down table toggle functions (from summary)
function toggleGroup(key, btn) {
    const tbl  = document.getElementById('svod-tbl');
    const rows = tbl.querySelectorAll('[data-parent="' + key + '"]');
    const expanding = btn.classList.contains('collapsed');
    btn.classList.toggle('collapsed', !expanding);
    btn.classList.toggle('expanded',   expanding);
    rows.forEach(function(row) {
        row.style.display = expanding ? '' : 'none';
        if (!expanding) {
            const childKey = row.dataset.key;
            if (childKey) collapseDescendants(tbl, childKey);
        }
    });
}
function collapseDescendants(tbl, key) {
    tbl.querySelectorAll('[data-parent="' + key + '"]').forEach(function(row) {
        row.style.display = 'none';
        var btn = row.querySelector('.tog');
        if (btn) { btn.classList.add('collapsed'); btn.classList.remove('expanded'); }
        var childKey = row.dataset.key;
        if (childKey) collapseDescendants(tbl, childKey);
    });
}
function expandAll() {
    document.querySelectorAll('#svod-tbl tbody tr').forEach(function(r) { r.style.display = ''; });
    document.querySelectorAll('.tog').forEach(function(b) {
        b.classList.add('expanded'); b.classList.remove('collapsed');
    });
}
function collapseAll() {
    document.querySelectorAll('#svod-tbl tbody [data-parent]').forEach(function(r) { r.style.display = 'none'; });
    document.querySelectorAll('.tog').forEach(function(b) {
        b.classList.add('collapsed'); b.classList.remove('expanded');
    });
}

document.getElementById('dm-prev').onclick = () => { if(modalState.page > 1){ modalState.page--; fetchModal(); } };
document.getElementById('dm-next').onclick = () => { if(modalState.page < modalState.lastPage){ modalState.page++; fetchModal(); } };
document.getElementById('detail-modal').addEventListener('click', e => { if(e.target === e.currentTarget) closeModal(); });
document.addEventListener('keydown', e => {
    if(e.key === 'Escape') {
        if(document.getElementById('contract-modal').classList.contains('open')) closeContract();
        else closeModal();
    }
});
</script>
@endpush
