@extends('layouts.app')

@section('title', 'АПЗ Свод — Туманлар кесимида')

@push('styles')
<style>
/* ── Layout ── */
.tbl-block {
    background:#fff; border-radius:12px;
    box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #e8e8e8;
    overflow:hidden; margin-bottom:20px;
}
.tbl-block-header {
    padding:14px 20px; border-bottom:1px solid #e8e8e8;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
}
.tbl-block-header .title { font-size:.95rem; font-weight:600; color:#15191e; }
.tbl-block-header .sub   { font-size:.75rem; color:#6e788b; }

/* ── Report banner ── */
.report-band {
    background:linear-gradient(100deg,#f0f9f8,#e6f7f6);
    border:1px solid #b2e4e1; border-radius:12px;
    padding:18px 24px; margin-bottom:20px;
    display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;
}
.report-band h1  { font-size:1rem; font-weight:700; color:#015c58; margin:0 0 4px; }
.report-band .rdate { font-size:.78rem; color:#6e788b; }
.report-band .rtag {
    display:inline-block; background:#018c87; color:#fff;
    font-size:.72rem; font-weight:700; letter-spacing:.06em;
    text-transform:uppercase; padding:3px 12px; border-radius:20px;
}

/* ── Main merged table ── */
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
.svod-table thead tr.hdr-group th.g-pay  { background:#018c87; }
.svod-table thead tr.hdr-group th.g-pf   { background:#1d6954; }
.svod-table thead tr.hdr-cols  th.g-pf   { background:#1d6954; border-color:#175945; }

/* ── Body rows ── */
.svod-table tbody td {
    padding:7px 10px; border:1px solid #ebebeb; color:#27314b; vertical-align:middle;
}
.svod-table tbody tr { transition:background .1s; }
.svod-table tbody tr:hover { background:#e8f7f6 !important; }
.svod-table tbody td.num  { text-align:right; font-weight:500; }
/* no heavy border — just a subtle teal left border on plan column */
.svod-table tbody td.sep  { border-left:1px solid #a8d8d5 !important; }

/* ── District (top-level) ── */
.row-district td    { background:#f4fefe; font-weight:600; }
.row-district .d-name { display:flex; align-items:center; gap:6px; }

/* ── Year rows ── */
.row-year td    { background:#edf8f7; font-weight:600; font-size:.8rem; }
.row-year .d-name { padding-left:18px; display:flex; align-items:center; gap:6px; }

/* ── Month rows ── */
.row-month td   { background:#f5fbfb; font-size:.78rem; }
.row-month .d-name { padding-left:36px; display:flex; align-items:center; gap:6px; }

/* ── Day rows — same compact height as month rows ── */
.row-day td     { background:#fff; font-size:.76rem; color:#444; padding:5px 10px; }
.row-day .d-name { padding-left:52px; display:flex; align-items:center; gap:6px; }

/* ── Total rows ── */
.row-total td   {
    background:#e8f4f3; font-weight:700; color:#015c58;
    border-top:2px solid #018c87; border-bottom:2px solid #018c87;
}

/* ── Toggle button ── */
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

/* ── Progress bar ── */
.pbar-wrap { background:#e4e4e4; border-radius:3px; height:7px; min-width:60px; }
.pbar      { height:7px; border-radius:3px; background:#018c87; }
.pbar.over { background:#e63260; }

/* ── Day row type: thin left accent only, no heavy bg ── */
.row-day--plan-only td:first-child { border-left:3px solid #f0a500 !important; }
.row-day--fact-only td:first-child { border-left:3px solid #1aad4e !important; }
.row-day--both      td:first-child { border-left:3px solid #018c87 !important; }

/* ── Day-type badge — compact inline pill ── */
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

/* ── Print / controls ── */
.print-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 18px; background:#018c87; color:#fff;
    border:none; border-radius:8px; font-size:.8rem; font-weight:600;
    cursor:pointer; transition:all .15s; text-decoration:none;
}
.print-btn:hover { background:#017570; color:#fff; }

@media print {
    .platon-header,.platon-aside,.no-print { display:none !important; }
    .platon-main { margin-left:0 !important; }
    .tbl-block { box-shadow:none; border:1px solid #ccc; }
    .tog { display:none !important; }
    .row-year,.row-month,.row-day { display:table-row !important; }
}
</style>
@endpush

@section('content')

{{-- Report header --}}
<div class="report-band">
    <div>
        <h1>АПЗ тўловлари ва План — Факт (Туманлар кесимида)</h1>
        <div class="rdate">
            {{ now()->format('d.m.Y') }} &nbsp;&middot;&nbsp;
            @if($selectedYear) {{ $selectedYear }} йил @else Барча йиллар @endif
            &nbsp;&middot;&nbsp; <strong>млн.сўм</strong>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;" class="no-print">
        <span class="rtag">АПЗ СВОД</span>
        <div style="display:flex;gap:8px;align-items:center;">
            <form method="GET" action="{{ route('summary') }}" style="display:flex;gap:6px;align-items:center;">
                <select name="year" class="form-select form-select-sm" style="width:110px;" onchange="this.form.submit()">
                    <option value="">Барча йил</option>
                    @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <button onclick="expandAll()" class="print-btn" style="background:#6e788b;">+ Барчасини очиш</button>
            <button onclick="collapseAll()" class="print-btn" style="background:#6e788b;">− Барчасини юм</button>
            <button onclick="window.print()" class="print-btn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/>
                </svg>
                Чоп
            </button>
        </div>
    </div>
</div>

{{-- Merged table --}}
<div class="tbl-block">
    <div class="tbl-block-header">
        <span class="title">Туман &rarr; Йил &rarr; Ой &rarr; Кун (дрилл-даун)</span>
        <span class="sub">млн.сўм &middot; + босиб кенгайтиринг</span>
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

        {{-- ══ GRAND TOTAL row ══ --}}
        @php
            $grandPlan = array_sum(array_column($planFact, 'plan'));
            $grandFact = array_sum(array_column($planFact, 'fact'));
            $grandBal  = $grandPlan - $grandFact;
            $grandPct  = $grandPlan > 0 ? round($grandFact / $grandPlan * 100, 1) : 0;
            $grandBarW = min($grandPct, 100);
        @endphp
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

        {{-- ══ Per district rows ══ --}}
        @foreach($summaryData as $row)
        @php
            $pf      = $planFact[$row['district']] ?? ['plan'=>0,'fact'=>0,'pct'=>0,'balance'=>0];
            $isOver  = $pf['pct'] > 100;
            $barW    = min($pf['pct'], 100);
            $dKey    = 'd_' . Str::slug($row['district']);
            // collect years that have data for this district
            $distYears = [];
            foreach ($drill as $yr => $months) {
                foreach ($months as $mo => $days) {
                    foreach ($days as $day => $dists) {
                        if (isset($dists[$row['district']])) {
                            $distYears[$yr] = true;
                        }
                    }
                }
            }
            ksort($distYears);
        @endphp

        {{-- District row --}}
        <tr class="row-district" data-level="district" data-key="{{ $dKey }}">
            <td>
                <div class="d-name">
                    @if(count($distYears))
                    <button class="tog collapsed" onclick="toggleGroup('{{ $dKey }}',this)" title="Очиш / Йум"></button>
                    @endif
                    {{ $row['district'] }}
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
            $yrKey = $dKey . '_y' . $yr;
            // Aggregate year totals for this district
            $yrIncome = $yrApz = $yrPen = $yrRef = $yrCnt = 0;
            foreach (($drill[$yr] ?? []) as $mo => $days) {
                foreach ($days as $day => $dists) {
                    if (isset($dists[$row['district']])) {
                        $r2 = $dists[$row['district']];
                        $yrIncome += $r2->income;
                        $yrApz    += $r2->apz;
                        $yrPen    += $r2->pen;
                        $yrRef    += $r2->ref;
                        $yrCnt    += $r2->cnt;
                    }
                }
            }
            // Plan stays the same (whole contract plan), fact is year-slice
            $yrFact    = $pfYear[$yr][$row['district']] ?? 0;
            $yrPlan    = $pf['plan'];  // total plan for this district
            $yrBal     = $yrPlan - $yrFact;
            $yrPct     = $yrPlan > 0 ? round($yrFact / $yrPlan * 100, 1) : 0;
            $yrBarW    = min($yrPct, 100);
            $yrIsOver  = $yrPct > 100;
        @endphp
        <tr class="row-year" data-parent="{{ $dKey }}" data-key="{{ $yrKey }}" style="display:none;">
            <td>
                <div class="d-name">
                    <button class="tog collapsed" onclick="toggleGroup('{{ $yrKey }}',this)" title="Ойларни очиш/юм"></button>
                    <strong>{{ $yr }} йил</strong>
                </div>
            </td>
            <td class="num">{{ $yrCnt }}</td>
            <td class="num">{{ number_format($yrIncome, 2, '.', ' ') }}</td>
            <td class="num">{{ $yrApz > 0 ? number_format($yrApz, 2, '.', ' ') : '—' }}</td>
            <td class="num">{{ $yrPen > 0 ? number_format($yrPen, 2, '.', ' ') : '—' }}</td>
            <td class="num">{{ $yrRef > 0 ? number_format($yrRef, 2, '.', ' ') : '—' }}</td>
            <td class="num sep" style="color:#6e788b;">{{ $yrPlan > 0 ? number_format($yrPlan, 2, '.', ' ') : '—' }}</td>
            <td class="num" style="color:#0a8a2e;">{{ $yrFact > 0 ? number_format($yrFact, 2, '.', ' ') : '—' }}</td>
            <td class="num" style="color:{{ $yrBal <= 0 ? '#0bc33f' : '#e05' }};">
                {{ $yrPlan > 0 ? number_format($yrBal, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="color:{{ $yrIsOver ? '#0bc33f' : '#27314b' }};">
                {{ $yrPlan > 0 ? number_format($yrPct, 1).'%' : '—' }}
            </td>
            <td>
                @if($yrPlan > 0)
                <div style="display:flex;align-items:center;gap:4px;">
                    <div class="pbar-wrap" style="flex:1;"><div class="pbar {{ $yrIsOver ? 'over' : '' }}" style="width:{{ $yrBarW }}%;"></div></div>
                    <span style="font-size:.68rem;color:#6e788b;">{{ number_format($yrPct,1) }}%</span>
                </div>
                @else <span style="color:#bbb;">—</span>
                @endif
            </td>
        </tr>

        {{-- Month level --}}
        @foreach(($drill[$yr] ?? []) as $mo => $days)
        @php
            $moKey = $yrKey . '_m' . Str::slug($mo);
            $moIncome = $moApz = $moPen = $moRef = $moCnt = 0;
            $hasDist = false;
            foreach ($days as $day => $dists) {
                if (isset($dists[$row['district']])) {
                    $r2 = $dists[$row['district']];
                    $moIncome += $r2->income; $moApz += $r2->apz;
                    $moPen    += $r2->pen;    $moRef  += $r2->ref;
                    $moCnt    += $r2->cnt;    $hasDist = true;
                }
            }
            if (!$hasDist) continue;
            // Month fact slice (plan stays district total)
            $moFact   = $pfMonth[$yr][$mo][$row['district']] ?? 0;
            $moPlanV  = $pf['plan'];
            $moBal    = $moPlanV - $moFact;
            $moPct    = $moPlanV > 0 ? round($moFact / $moPlanV * 100, 1) : 0;
            $moBarW   = min($moPct, 100);
            $moIsOver = $moPct > 100;
        @endphp
        <tr class="row-month" data-parent="{{ $yrKey }}" data-key="{{ $moKey }}" style="display:none;">
            <td>
                <div class="d-name">
                    <button class="tog collapsed" onclick="toggleGroup('{{ $moKey }}',this)" title="Кунларни очиш/юм"></button>
                    {{ $mo }}
                </div>
            </td>
            <td class="num">{{ $moCnt }}</td>
            <td class="num">{{ number_format($moIncome, 2, '.', ' ') }}</td>
            <td class="num">{{ $moApz > 0 ? number_format($moApz, 2, '.', ' ') : '—' }}</td>
            <td class="num">{{ $moPen > 0 ? number_format($moPen, 2, '.', ' ') : '—' }}</td>
            <td class="num">{{ $moRef > 0 ? number_format($moRef, 2, '.', ' ') : '—' }}</td>
            <td class="num sep" style="color:#6e788b;font-size:.76rem;">{{ $moPlanV > 0 ? number_format($moPlanV, 2, '.', ' ') : '—' }}</td>
            <td class="num" style="color:#0a8a2e;">{{ $moFact > 0 ? number_format($moFact, 2, '.', ' ') : '—' }}</td>
            <td class="num" style="color:{{ $moBal <= 0 ? '#0bc33f' : '#e05' }};">
                {{ $moPlanV > 0 ? number_format($moBal, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="color:{{ $moIsOver ? '#0bc33f' : '#27314b' }};">
                {{ $moPlanV > 0 ? number_format($moPct, 1).'%' : '—' }}
            </td>
            <td>
                @if($moPlanV > 0)
                <div style="display:flex;align-items:center;gap:4px;">
                    <div class="pbar-wrap" style="flex:1;"><div class="pbar {{ $moIsOver ? 'over' : '' }}" style="width:{{ $moBarW }}%;"></div></div>
                    <span style="font-size:.68rem;color:#6e788b;">{{ number_format($moPct,1) }}%</span>
                </div>
                @else <span style="color:#bbb;">—</span>
                @endif
            </td>
        </tr>

        {{-- Day level: merge plan dates + fact dates into a unified timeline --}}
        @php
            // Collect all unique dates: fact dates + plan schedule dates for this month
            $factDays   = array_keys($days);  // keys = 'YYYY-MM-DD'
            $schedDays  = array_keys($planSchedule[$row['district']][$yr][$mo] ?? []);
            $allDays    = array_unique(array_merge($factDays, $schedDays));
            sort($allDays);
        @endphp
        @foreach($allDays as $dayKey)
        @php
            $factRow     = $days[$dayKey][$row['district']] ?? null;
            $planAmt     = $planSchedule[$row['district']][$yr][$mo][$dayKey] ?? 0;
            $factAmt     = $factRow ? $factRow->income : 0;
            $factApz     = $factRow ? $factRow->apz   : 0;
            $factPen     = $factRow ? $factRow->pen   : 0;
            $factRef     = $factRow ? $factRow->ref   : 0;
            $factCnt     = $factRow ? $factRow->cnt   : 0;
            $dayBal      = $planAmt - $factAmt;
            $dayPct      = $planAmt > 0 ? round($factAmt / $planAmt * 100, 1) : 0;
            $dayBarW     = min($dayPct, 100);
            $hasPlan     = $planAmt > 0;
            $hasFact     = $factAmt > 0;
            // Row type: both, plan-only, fact-only
            $dayType = ($hasPlan && $hasFact) ? 'both' : ($hasPlan ? 'plan-only' : 'fact-only');
        @endphp
        <tr class="row-day row-day--{{ $dayType }}" data-parent="{{ $moKey }}" style="display:none;">
            <td>
                <div class="d-name">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#aab0bb" stroke-width="2.5"><circle cx="12" cy="12" r="4"/></svg>
                    <span style="font-weight:500;color:#27314b;">{{ date('d.m.Y', strtotime($dayKey)) }}</span>
                    @if($hasPlan && !$hasFact)
                        <span class="day-badge day-badge--plan">П</span>
                    @elseif(!$hasPlan && $hasFact)
                        <span class="day-badge day-badge--fact">Ф</span>
                    @else
                        <span class="day-badge day-badge--both">П+Ф</span>
                    @endif
                </div>
            </td>
            <td class="num" style="color:#888;font-size:.76rem;">
                {{ $factCnt > 0 ? $factCnt : '—' }}
            </td>
            <td class="num">
                {{ $hasFact ? number_format($factAmt, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="font-size:.76rem;">
                {{ $factApz > 0 ? number_format($factApz, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="font-size:.76rem;">
                {{ $factPen > 0 ? number_format($factPen, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="font-size:.76rem;">
                {{ $factRef > 0 ? number_format($factRef, 2, '.', ' ') : '—' }}
            </td>
            {{-- Plan-Fact columns: same styling as month/year rows --}}
            <td class="num sep" style="color:{{ $hasPlan ? '#015c58' : '#ccc' }};">
                {{ $hasPlan ? number_format($planAmt, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="color:{{ $hasFact ? '#0a8a2e' : '#bbb' }};">
                {{ $hasFact ? number_format($factAmt, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="color:{{ $hasPlan ? ($dayBal <= 0 ? '#0bc33f' : '#e63260') : '#bbb' }};">
                {{ $hasPlan ? number_format($dayBal, 2, '.', ' ') : '—' }}
            </td>
            <td class="num" style="color:{{ $dayPct >= 100 ? '#0bc33f' : '#555' }};">
                {{ $hasPlan ? number_format($dayPct, 1).'%' : '—' }}
            </td>
            <td style="padding:5px 8px;">
                @if($hasPlan && $hasFact)
                <div style="display:flex;align-items:center;gap:3px;">
                    <div class="pbar-wrap" style="flex:1;height:5px;"><div class="pbar {{ $dayPct > 100 ? 'over' : '' }}" style="width:{{ $dayBarW }}%;height:5px;"></div></div>
                    <span style="font-size:.65rem;color:#6e788b;">{{ number_format($dayPct,1) }}%</span>
                </div>
                @elseif($hasPlan)
                    <span style="font-size:.68rem;color:#e63260;">&#8212; кутлмади</span>
                @elseif($hasFact)
                    <span style="font-size:.68rem;color:#0a8a2e;">&#10003; тўлов</span>
                @endif
            </td>
        </tr>
        @endforeach {{-- /allDays --}}

        @endforeach {{-- /months --}}
        @endforeach {{-- /years --}}
        @endforeach {{-- /districts --}}

        </tbody>
    </table>
    </div>
</div>

@push('scripts')
<script>
// Toggle direct children of a group key
function toggleGroup(key, btn) {
    const tbl  = document.getElementById('svod-tbl');
    const rows = tbl.querySelectorAll('[data-parent="' + key + '"]');
    const expanding = btn.classList.contains('collapsed');
    btn.classList.toggle('collapsed', !expanding);
    btn.classList.toggle('expanded',   expanding);
    rows.forEach(function(row) {
        row.style.display = expanding ? '' : 'none';
        // If collapsing, also collapse all descendants
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
</script>
@endpush

@endsection

@push('styles')
<style>
    .tbl-block {
        background:#fff; border-radius:12px;
        box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #e8e8e8;
        overflow:hidden; margin-bottom:20px;
    }
    .tbl-block-header {
        padding:14px 20px; border-bottom:1px solid #e8e8e8;
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
    }
    .tbl-block-header .title { font-size:.95rem; font-weight:600; color:#15191e; }
    .tbl-block-header .sub   { font-size:.75rem; color:#6e788b; }

    .report-band {
        background:linear-gradient(100deg,#f0f9f8,#e6f7f6);
        border:1px solid #b2e4e1; border-radius:12px;
        padding:18px 24px; margin-bottom:20px;
        display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;
    }
    .report-band h1  { font-size:1rem; font-weight:700; color:#015c58; margin:0 0 4px; }
    .report-band .rdate { font-size:.78rem; color:#6e788b; }
    .report-band .rtag  {
        display:inline-block; background:#018c87; color:#fff;
        font-size:.72rem; font-weight:700; letter-spacing:.06em;
        text-transform:uppercase; padding:3px 12px; border-radius:20px;
    }

    .svod-table { width:100%; border-collapse:collapse; font-size:.84rem; }
    .svod-table thead tr.hdr-group th {
        padding:7px 10px; font-weight:700; font-size:.72rem;
        color:#fff; border:1px solid rgba(255,255,255,.25);
        text-align:center; letter-spacing:.04em; text-transform:uppercase;
    }
    .svod-table thead tr.hdr-cols th {
        padding:9px 10px; font-weight:600; font-size:.73rem;
        color:#fff; background:#018c87; border:1px solid #017570;
        text-align:center; vertical-align:middle; line-height:1.3; white-space:nowrap;
    }
    .svod-table thead tr.hdr-cols th:first-child { text-align:left; }
    .svod-table thead tr.hdr-group th.g-payments { background:#018c87; }
    .svod-table thead tr.hdr-group th.g-planfact  { background:#1d6954; }
    .svod-table thead tr.hdr-cols  th.g-planfact  { background:#1d6954; border-color:#175945; }
    .svod-table tbody tr { border-bottom:1px solid #e8e8e8; transition:background .1s; }
    .svod-table tbody tr:hover { background:#f0f9f8; }
    .svod-table tbody td { padding:10px 10px; border:1px solid #ebebeb; color:#27314b; }
    .svod-table tbody td.num { text-align:right; font-weight:500; }
    .svod-table tbody td.district-name { font-weight:600; color:#15191e; }
    .svod-table .total-row td {
        background:#e8f4f3; font-weight:700; color:#015c58;
        border-top:2px solid #018c87; border-bottom:2px solid #018c87;
    }
    .svod-table td.sep { border-left:2px solid #1d6954 !important; }
    .progress-bar-wrap { background:#e4e4e4; border-radius:4px; height:7px; min-width:70px; }
    .progress-bar-fill { height:7px; border-radius:4px; background:#018c87; }
    .progress-bar-fill.over { background:#e63260; }

    .print-btn {
        display:inline-flex; align-items:center; gap:8px;
        padding:10px 22px; background:#018c87; color:#fff;
        border:none; border-radius:8px; font-size:.875rem; font-weight:600;
        cursor:pointer; transition:all .15s; text-decoration:none;
    }
    .print-btn:hover { background:#017570; color:#fff; }

    @media print {
        .platon-header,.platon-aside,.print-btn,.no-print { display:none !important; }
        .platon-main { margin-left:0 !important; }
        .tbl-block { box-shadow:none; border:1px solid #ccc; }
    }
</style>
@endpush

@section('content')

{{-- Report header --}}
<div class="report-band">
    <div>
        <h1>АПЗ тўловлари бўйича свод — Туманлар кесимида</h1>
        <div class="rdate">
            {{ now()->format('d.m.Y') }} &nbsp;·&nbsp;
            @if($selectedYear) {{ $selectedYear }} йил @else Барча йиллар @endif
            &nbsp;·&nbsp; <strong>млн.сўм</strong>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
        <span class="rtag">АПЗ СВОД</span>
        <div style="display:flex;gap:8px;align-items:center;" class="no-print">
            {{-- Year filter --}}
            <form method="GET" action="{{ route('summary') }}" style="display:flex;gap:6px;align-items:center;">
                <select name="year" class="form-select form-select-sm" style="width:110px;"
                    onchange="this.form.submit()">
                    <option value="">Барча йил</option>
                    @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <button onclick="window.print()" class="print-btn" style="padding:8px 16px;font-size:.8rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <path d="M6 14h12v8H6z"/>
                </svg>
                Чоп этиш
            </button>
        </div>
    </div>
</div>

{{-- Single merged table: Payments + Plan-Fact --}}
<div class="tbl-block">
    <div class="tbl-block-header">
        <span class="title">АПЗ тўловлари ва План — Факт (Туманлар кесимида)</span>
        <span class="sub">млн.сўм</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="svod-table">
            <thead>
                {{-- Group row --}}
                <tr class="hdr-group">
                    <th rowspan="2" class="g-payments" style="text-align:left;min-width:130px;">Туман</th>
                    <th colspan="5" class="g-payments">АПЗ тўловлари (факт, млн.сўм)</th>
                    <th colspan="5" class="g-planfact">План — Факт</th>
                </tr>
                {{-- Column row --}}
                <tr class="hdr-cols">
                    <th class="g-payments">Шартномалар<br>сони</th>
                    <th class="g-payments">Жами тушум</th>
                    <th class="g-payments">АПЗ тўлови</th>
                    <th class="g-payments">Пеня</th>
                    <th class="g-payments">Қайтариш</th>
                    <th class="g-planfact sep">План</th>
                    <th class="g-planfact">Факт</th>
                    <th class="g-planfact">Қолдиқ</th>
                    <th class="g-planfact">%</th>
                    <th class="g-planfact">Прогресс</th>
                </tr>
            </thead>
            <tbody>
                {{-- Totals row --}}
                @php
                    $grandPlan = array_sum(array_column($planFact, 'plan'));
                    $grandFact = array_sum(array_column($planFact, 'fact'));
                    $grandBal  = $grandPlan - $grandFact;
                    $grandPct  = $grandPlan > 0 ? round($grandFact / $grandPlan * 100, 1) : 0;
                    $grandBarW = min($grandPct, 100);
                @endphp
                <tr class="total-row">
                    <td class="district-name">ЖАМИ</td>
                    <td class="num">{{ number_format($totals['contract_count']) }}</td>
                    <td class="num">{{ number_format($totals['total_income'], 2, '.', ' ') }}</td>
                    <td class="num">{{ number_format($totals['apz_payment'], 2, '.', ' ') }}</td>
                    <td class="num">{{ number_format($totals['penalty'], 2, '.', ' ') }}</td>
                    <td class="num">{{ number_format($totals['refund'], 2, '.', ' ') }}</td>
                    <td class="num sep">{{ number_format($grandPlan, 2, '.', ' ') }}</td>
                    <td class="num">{{ number_format($grandFact, 2, '.', ' ') }}</td>
                    <td class="num" style="color:{{ $grandBal <= 0 ? '#0bc33f' : '#e63260' }};">{{ number_format($grandBal, 2, '.', ' ') }}</td>
                    <td class="num" style="font-weight:700;color:{{ $grandPct >= 100 ? '#0bc33f' : '#27314b' }};">{{ $grandPct }}%</td>
                    <td>
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill {{ $grandPct > 100 ? 'over' : '' }}" style="width:{{ $grandBarW }}%;"></div>
                        </div>
                    </td>
                </tr>
                @foreach($summaryData as $row)
                @php
                    $pf      = $planFact[$row['district']] ?? ['plan'=>0,'fact'=>0,'pct'=>0,'balance'=>0];
                    $isOver  = $pf['pct'] > 100;
                    $barW    = min($pf['pct'], 100);
                @endphp
                <tr>
                    <td class="district-name">{{ $row['district'] }}</td>
                    <td class="num">{{ $row['contract_count'] }}</td>
                    <td class="num">{{ number_format($row['total_income'], 2, '.', ' ') }}</td>
                    <td class="num">{{ $row['apz_payment'] > 0 ? number_format($row['apz_payment'], 2, '.', ' ') : '—' }}</td>
                    <td class="num">{{ $row['penalty'] > 0 ? number_format($row['penalty'], 2, '.', ' ') : '—' }}</td>
                    <td class="num">{{ $row['refund'] > 0 ? number_format($row['refund'], 2, '.', ' ') : '—' }}</td>
                    <td class="num sep">{{ $pf['plan'] > 0 ? number_format($pf['plan'], 2, '.', ' ') : '—' }}</td>
                    <td class="num" style="color:#0a8a2e;">{{ $pf['fact'] > 0 ? number_format($pf['fact'], 2, '.', ' ') : '—' }}</td>
                    <td class="num" style="color:{{ $pf['balance'] <= 0 ? '#0bc33f' : '#e63260' }};font-weight:600;">
                        {{ $pf['plan'] > 0 ? number_format($pf['balance'], 2, '.', ' ') : '—' }}
                    </td>
                    <td class="num" style="font-weight:700;color:{{ $isOver ? '#0bc33f' : '#27314b' }};">
                        {{ $pf['plan'] > 0 ? $pf['pct'] . '%' : '—' }}
                    </td>
                    <td style="min-width:80px;">
                        @if($pf['plan'] > 0)
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div class="progress-bar-wrap" style="flex:1;">
                                <div class="progress-bar-fill {{ $isOver ? 'over' : '' }}" style="width:{{ $barW }}%;"></div>
                            </div>
                            <span style="font-size:.7rem;color:#6e788b;white-space:nowrap;">{{ $pf['pct'] }}%</span>
                        </div>
                        @else
                        <span style="color:#bbb;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
