@extends('layouts.app')

@section('title', 'Қарздорликлар ҳисоботи')

@push('styles')
<style>
    .report-container {
        background:#fff; border-radius:12px;
        box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #e8e8e8;
        padding:24px;
    }
    .report-header { text-align:center; margin-bottom:22px; padding-bottom:16px; border-bottom:2px solid #018c87; }
    .report-header h1 { font-size:1.1rem; font-weight:700; margin-bottom:6px; color:#15191e; }
    .report-header .date { font-size:.82rem; color:#6e788b; }

    .grand-stat { text-align:center; padding:16px 20px; border-radius:10px; border:1px solid #e0e0e0; }
    .grand-stat .val { font-size:1.35rem; font-weight:800; color:#018c87; }
    .grand-stat .lbl { font-size:.72rem; color:#6e788b; margin-top:4px; text-transform:uppercase; letter-spacing:.05em; }
    .grand-stat.debt-card {
        border-color:#efb6b6;
        background:linear-gradient(180deg, #fff6f6 0%, #fff 100%);
    }
    .grand-stat.debt-card .lbl { color:#9b3a3a; }

    .contracts-table { width:100%; border-collapse:collapse; font-size:.82rem; }
    .contracts-table thead th {
        padding:10px 8px; text-align:center; font-weight:600;
        color:#fff; background:#018c87; border:1px solid #017570; white-space:nowrap;
    }
    .contracts-table tbody td { padding:8px 8px; border:1px solid #e4e4e4; }
    .contracts-table tbody tr:nth-child(even) { background:#f8fafb; }
    .contracts-table tbody tr:hover { background:#e8f4f3; }
    .contracts-table tbody tr.clickable { cursor:pointer; }
    .contracts-table tbody td.r { text-align:right; }
    .contracts-table tbody td.c { text-align:center; }
    .total-row td { background:#e8f4f3 !important; font-weight:700; border-top:2px solid #018c87; }

    .status-badge,
    .issue-badge {
        display:inline-block; padding:3px 9px; border-radius:6px;
        font-size:.71rem; font-weight:600; white-space:nowrap;
    }
    .status-inprogress { background:rgba(1,140,135,.12); color:#018c87; }
    .status-completed  { background:rgba(6,184,56,.1); color:#0a8a2e; }
    .status-cancelled  { background:rgba(230,50,96,.1); color:#c02050; }

    .issue-problem    { background:rgba(230,50,96,.1); color:#c02050; }
    .issue-ok         { background:rgba(6,184,56,.1);  color:#0a8a2e; }
    .issue-unknown    { background:#f1f3f5; color:#7f8a9b; }

    .diff-red    { color:#e63260; font-weight:600; }
    .diff-orange { color:#d47000; font-weight:600; }
    .diff-yellow { color:#9a6800; font-weight:600; }
    .diff-green  { color:#0a8a2e; font-weight:600; }
    .diff-muted  { color:#7f8a9b; font-weight:600; }
    .txt-good { color:#0a8a2e; }
    .txt-danger { color:#e63260; }
    .txt-pending { color:#d47000; }
    .debt-red-strong { color:#c62828; }
    .debt-red-soft { color:#d84343; }
    .link-clean { color:#018c87; text-decoration:none; }

    .pg-wrap { display:flex; align-items:center; justify-content:center; gap:6px; flex-wrap:wrap; margin-top:16px; }
    .pg-btn {
        padding:5px 14px; border:1px solid #d0d0d0; border-radius:6px;
        background:#fff; color:#27314b; font-size:.8rem; cursor:pointer; text-decoration:none;
    }
    .pg-btn:hover { background:#f0f9f8; border-color:#018c87; color:#018c87; }
    .pg-btn.active { background:#018c87; border-color:#018c87; color:#fff; font-weight:700; pointer-events:none; }
    .pg-btn:disabled, .pg-btn.disabled { opacity:.4; pointer-events:none; }
    .pg-info { font-size:.78rem; color:#6e788b; }

    @media print {
        .platon-header,.platon-aside,.no-print { display:none !important; }
        .platon-main { margin-left:0 !important; }
        .report-container { box-shadow:none; border:none; }
    }
</style>
@endpush

@section('content')
<div class="report-container">

    <div class="report-header">
        <h1>Шартномалар бўйича қарздорлик ҳисоботи</h1>
        <div class="date">{{ $reportDate }}</div>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-3">
            <div class="grand-stat">
                <div class="val">{{ $summaryStats['total_contracts'] }}</div>
                <div class="lbl">Жами шартномалар</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat">
                <div class="val">{{ $summaryStats['grand_plan_mln'] }}</div>
                <div class="lbl">Жами шартнома қиймати (сўм)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat debt-card">
                <div class="val debt-red-strong">{{ $summaryStats['grand_debt_mln'] }}</div>
                <div class="lbl">Муддати ўтган қарздорлик (сўм)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat debt-card">
                <div class="val debt-red-soft">{{ $summaryStats['grand_unoverdue_debt_mln'] }}</div>
                <div class="lbl">Муддати келмаган қарздорлик (сўм)</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4">
            <div class="grand-stat">
                <div class="val txt-good">{{ $summaryStats['grand_fact_mln'] }}</div>
                <div class="lbl">Жами факт тўлов (сўм)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="grand-stat debt-card">
                <div class="val debt-red-strong">{{ $summaryStats['grand_total_debt_mln'] }}</div>
                <div class="lbl">Жами қарздорлик (умумий, сўм)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="grand-stat">
                <div class="val {{ $summaryStats['overall_pct_class'] }}">{{ $summaryStats['overall_pct'] }}</div>
                <div class="lbl">Бажарилиш фоизи</div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('debts') }}" class="d-flex gap-2 mb-3 no-print" style="flex-wrap:wrap;">
        <select name="status" class="form-select form-select-sm" style="width:220px;" onchange="this.form.submit()">
            @foreach($statusOptions as $option)
                <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <select name="district" class="form-select form-select-sm" style="width:190px;" onchange="this.form.submit()">
            @foreach($districtOptions as $option)
                <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <select name="issue" class="form-select form-select-sm" style="width:220px;" onchange="this.form.submit()">
            @foreach($issueOptions as $option)
                <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <select name="debt_type" class="form-select form-select-sm" style="width:240px;" onchange="this.form.submit()">
            @foreach($debtTypeOptions as $option)
                <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <input type="text" name="search" value="{{ $searchTerm ?? '' }}" class="form-control form-control-sm" style="width:240px;" placeholder="Компания / шартнома / ИНН / ID">
        <button type="submit" class="platon-btn platon-btn-outline platon-btn-sm">Қидириш</button>
        @if($showResetFilters)
            <a href="{{ route('debts') }}" class="platon-btn platon-btn-outline platon-btn-sm">Тозалаш</a>
        @endif
        <a href="{{ route('debts', ['status' => 'all', 'issue' => 'all', 'debt_type' => 'all', 'debtors' => 1, 'export' => 'xlsx']) }}" class="platon-btn platon-btn-outline platon-btn-sm">Export Debtors XLSX</a>
        <button onclick="window.print()" type="button" class="platon-btn platon-btn-primary platon-btn-sm" style="margin-left:auto;">Чоп</button>
    </form>

    <div class="table-responsive">
        <table class="contracts-table">
            <thead>
                <tr>
                    <th style="width:4%;">№</th>
                    <th style="width:20%;">Компания номи</th>
                    <th style="width:10%;">Туман</th>
                    <th style="width:10%;">Шартнома рақами</th>
                    <th style="width:9%;">Шартнома санаси</th>
                    <th style="width:10%;">Ҳолат</th>
                    <th style="width:11%;">Қурилиш ҳолати</th>
                    <th style="width:9%;">Шартнома қиймати</th>
                    <th style="width:9%;">Факт тўлаган</th>
                    <th style="width:9%;">Муддати ўтган қарздорлик</th>
                    <th style="width:9%;">Муддати келмаган қарздорлик</th>
                    <th style="width:9%;">План-Факт фарқи</th>
                </tr>
            </thead>
            <tbody>
                <tr class="total-row">
                    <td class="c">—</td>
                    <td colspan="6">ЖАМИ ({{ $total }} шартнома)</td>
                    <td class="r">{{ $summaryRow['plan_mln'] }}</td>
                    <td class="r txt-good">{{ $summaryRow['fact_mln'] }}</td>
                    <td class="r debt-red-strong">{{ $summaryRow['debt_mln'] }}</td>
                    <td class="r debt-red-soft">{{ $summaryRow['unoverdue_debt_mln'] }}</td>
                    <td class="r {{ $summaryRow['diff_class'] }}">{{ $summaryRow['diff_mln'] }}</td>
                </tr>

                @foreach($contracts as $contract)
                <tr class="clickable" onclick="window.location='{{ $contract['detail_url'] }}'">
                    <td class="c">{{ $contract['row_num'] }}</td>
                    <td>{{ $contract['investor_name'] }}</td>
                    <td class="c">{{ $contract['district'] }}</td>
                    <td class="c"><a href="{{ $contract['detail_url'] }}" class="link-clean" onclick="event.stopPropagation()">{{ $contract['contract_number'] }}</a></td>
                    <td class="c">{{ $contract['contract_date'] }}</td>
                    <td class="c"><span class="status-badge {{ $contract['status_class'] }}">{{ $contract['status_label'] }}</span></td>
                    <td class="c"><span class="issue-badge {{ $contract['issue_class'] }}">{{ $contract['issue_label'] }}</span></td>
                    <td class="r">{{ $contract['plan_mln'] }}</td>
                    <td class="r txt-good">{{ $contract['fact_mln'] }}</td>
                    <td class="r debt-red-strong" style="font-weight:600;">{{ $contract['debt_mln'] }}</td>
                    <td class="r debt-red-soft" style="font-weight:600;">{{ $contract['unoverdue_debt_mln'] }}</td>
                    <td class="r {{ $contract['diff_class'] }}">{{ $contract['diff_mln'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($lastPage > 1)
    <div class="pg-wrap no-print">
        @if($pagination['first_url'] && $pagination['prev_url'])
            <a href="{{ $pagination['first_url'] }}" class="pg-btn">&laquo;</a>
            <a href="{{ $pagination['prev_url'] }}" class="pg-btn">&lsaquo; Олдинги</a>
        @else
            <span class="pg-btn disabled">&laquo;</span>
            <span class="pg-btn disabled">&lsaquo; Олдинги</span>
        @endif

        @foreach($pagination['pages'] as $p)
            <a href="{{ $p['url'] }}" class="pg-btn {{ $p['active'] ? 'active' : '' }}">{{ $p['number'] }}</a>
        @endforeach

        @if($pagination['next_url'] && $pagination['last_url'])
            <a href="{{ $pagination['next_url'] }}" class="pg-btn">Кейинги &rsaquo;</a>
            <a href="{{ $pagination['last_url'] }}" class="pg-btn">&raquo;</a>
        @else
            <span class="pg-btn disabled">Кейинги &rsaquo;</span>
            <span class="pg-btn disabled">&raquo;</span>
        @endif

        <span class="pg-info">{{ $page }} / {{ $lastPage }} (Жами: {{ $total }})</span>
    </div>
    @endif

</div>
@endsection
