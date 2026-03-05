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

    .contracts-table { width:100%; border-collapse:collapse; font-size:.82rem; }
    .contracts-table thead th {
        padding:10px 8px; text-align:center; font-weight:600;
        color:#fff; background:#018c87; border:1px solid #017570; white-space:nowrap;
    }
    .contracts-table tbody td { padding:8px 8px; border:1px solid #e4e4e4; }
    .contracts-table tbody tr:nth-child(even) { background:#f8fafb; }
    .contracts-table tbody tr:hover { background:#e8f4f3; }
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
    .issue-noproblem  { background:rgba(6,184,56,.1);  color:#0a8a2e; }
    .issue-unknown    { background:#f1f3f5; color:#7f8a9b; }

    .diff-red    { color:#e63260; font-weight:600; }
    .diff-orange { color:#d47000; font-weight:600; }
    .diff-yellow { color:#9a6800; font-weight:600; }
    .diff-green  { color:#0a8a2e; font-weight:600; }
    .diff-muted  { color:#7f8a9b; font-weight:600; }

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
        <div class="date">{{ now()->format('d.m.Y') }}</div>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4">
            <div class="grand-stat">
                <div class="val">{{ number_format($total) }}</div>
                <div class="lbl">Жами шартномалар</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="grand-stat">
                <div class="val">{{ number_format($grandPlan / 1000000, 2, '.', ' ') }}</div>
                <div class="lbl">Жами шартнома қиймати (млн.сўм)</div>
            </div>
        </div>
        <div class="col-md-4">
            @php $grandDebt = max($grandPlan - $grandFact, 0); @endphp
            <div class="grand-stat">
                <div class="val" style="color:#e63260;">{{ number_format($grandDebt / 1000000, 2, '.', ' ') }}</div>
                <div class="lbl">Жами қарздорлик (млн.сўм)</div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('debts') }}" class="d-flex gap-2 mb-3 no-print" style="flex-wrap:wrap;">
        <select name="status" class="form-select form-select-sm" style="width:220px;" onchange="this.form.submit()">
            <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Барчаси</option>
            <option value="in_progress" {{ $selectedStatus === 'in_progress' ? 'selected' : '' }}>Амалдаги</option>
            <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Якунланган</option>
            <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Бекор қилинган</option>
        </select>
        <select name="district" class="form-select form-select-sm" style="width:190px;" onchange="this.form.submit()">
            <option value="">Туман: барчаси</option>
            @foreach(($availableDistricts ?? []) as $d)
                <option value="{{ $d }}" {{ ($selectedDistrict ?? null) === $d ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
        </select>
        <select name="issue" class="form-select form-select-sm" style="width:220px;" onchange="this.form.submit()">
            <option value="all" {{ ($selectedIssue ?? 'all') === 'all' ? 'selected' : '' }}>Муаммо ҳолати: барчаси</option>
            <option value="problem" {{ ($selectedIssue ?? 'all') === 'problem' ? 'selected' : '' }}>Муаммоли</option>
            <option value="no_problem" {{ ($selectedIssue ?? 'all') === 'no_problem' ? 'selected' : '' }}>Муаммосиз</option>
            <option value="unknown" {{ ($selectedIssue ?? 'all') === 'unknown' ? 'selected' : '' }}>Кўрсатилмаган</option>
        </select>
        <input type="text" name="search" value="{{ $searchTerm ?? '' }}" class="form-control form-control-sm" style="width:240px;" placeholder="Компания / шартнома / ИНН / ID">
        <button type="submit" class="platon-btn platon-btn-outline platon-btn-sm">Қидириш</button>
        @if($selectedStatus !== 'in_progress' || ($selectedIssue ?? 'all') !== 'all' || ($selectedDistrict ?? null) || ($searchTerm ?? null))
            <a href="{{ route('debts') }}" class="platon-btn platon-btn-outline platon-btn-sm">Тозалаш</a>
        @endif
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
                    <th style="width:9%;">Қарздорлик</th>
                    <th style="width:9%;">План-Факт фарқи</th>
                </tr>
            </thead>
            <tbody>
                <tr class="total-row">
                    <td class="c">—</td>
                    <td colspan="6">ЖАМИ ({{ $total }} шартнома)</td>
                    <td class="r">{{ number_format($grandPlan / 1000000, 2, '.', ' ') }}</td>
                    <td class="r" style="color:#0a8a2e;">{{ number_format($grandFact / 1000000, 2, '.', ' ') }}</td>
                    <td class="r" style="color:#e63260;">{{ number_format(max($grandPlan - $grandFact, 0) / 1000000, 2, '.', ' ') }}</td>
                    @php
                        $grandPct = $grandPlan > 0 ? round(($grandFact / $grandPlan) * 100, 1) : null;
                        $grandDiffClass = match (true) {
                            $grandPct === null => 'diff-muted',
                            $grandPct < 10 => 'diff-red',
                            $grandPct < 35 => 'diff-orange',
                            $grandPct < 60 => 'diff-yellow',
                            default => 'diff-green',
                        };
                    @endphp
                    <td class="r {{ $grandDiffClass }}">
                        {{ number_format(($grandPlan - $grandFact) / 1000000, 2, '.', ' ') }}
                    </td>
                </tr>

                @foreach($contracts as $i => $c)
                @php
                    $plan = (float) ($c->contract_value ?? 0);
                    $fact = (float) ($c->total_paid ?? 0);
                    $debt = (float) ($c->debt ?? 0);
                    $diff = (float) ($c->plan_fact_diff ?? 0);
                    $pct  = $plan > 0 ? round(($fact / $plan) * 100, 1) : null;
                    $rowNum = ($page - 1) * $perPage + $i + 1;

                    $statusClass = match($c->status_key ?? 'in_progress') {
                        'completed' => 'status-completed',
                        'cancelled' => 'status-cancelled',
                        default     => 'status-inprogress',
                    };
                    $issueClass = match($c->issue_key ?? 'unknown') {
                        'problem'    => 'issue-problem',
                        'no_problem' => 'issue-noproblem',
                        default      => 'issue-unknown',
                    };

                    $diffClass = match (true) {
                        $pct === null => 'diff-muted',
                        $pct < 10 => 'diff-red',
                        $pct < 35 => 'diff-orange',
                        $pct < 60 => 'diff-yellow',
                        default => 'diff-green',
                    };
                @endphp
                <tr>
                    <td class="c">{{ $rowNum }}</td>
                    <td>{{ $c->investor_name ?: '—' }}</td>
                    <td class="c">{{ $c->district ?: '—' }}</td>
                    <td class="c">{{ $c->contract_number ?: '—' }}</td>
                    <td class="c">{{ $c->contract_date ? \Carbon\Carbon::parse($c->contract_date)->format('d.m.Y') : '—' }}</td>
                    <td class="c"><span class="status-badge {{ $statusClass }}">{{ $c->status_label }}</span></td>
                    <td class="c"><span class="issue-badge {{ $issueClass }}">{{ $c->issue_label }}</span></td>
                    <td class="r">{{ $plan > 0 ? number_format($plan / 1000000, 2, '.', ' ') : '—' }}</td>
                    <td class="r" style="color:#0a8a2e;">{{ $fact > 0 ? number_format($fact / 1000000, 2, '.', ' ') : '—' }}</td>
                    <td class="r" style="color:#e63260;font-weight:600;">{{ $debt > 0 ? number_format($debt / 1000000, 2, '.', ' ') : '0.00' }}</td>
                    <td class="r {{ $diffClass }}">
                        {{ number_format($diff / 1000000, 2, '.', ' ') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($lastPage > 1)
    <div class="pg-wrap no-print">
        @php
            $qs = [
                'status' => $selectedStatus,
                'district' => ($selectedDistrict ?? null),
                'issue' => ($selectedIssue ?? 'all'),
                'search' => ($searchTerm ?? null),
            ];
        @endphp

        @if($page > 1)
            <a href="{{ route('debts', array_merge($qs,['page'=>1])) }}" class="pg-btn">&laquo;</a>
            <a href="{{ route('debts', array_merge($qs,['page'=>$page-1])) }}" class="pg-btn">&lsaquo; Олдинги</a>
        @else
            <span class="pg-btn disabled">&laquo;</span>
            <span class="pg-btn disabled">&lsaquo; Олдинги</span>
        @endif

        @for($p = max(1,$page-2); $p <= min($lastPage,$page+2); $p++)
            <a href="{{ route('debts', array_merge($qs,['page'=>$p])) }}" class="pg-btn {{ $p==$page ? 'active' : '' }}">{{ $p }}</a>
        @endfor

        @if($page < $lastPage)
            <a href="{{ route('debts', array_merge($qs,['page'=>$page+1])) }}" class="pg-btn">Кейинги &rsaquo;</a>
            <a href="{{ route('debts', array_merge($qs,['page'=>$lastPage])) }}" class="pg-btn">&raquo;</a>
        @else
            <span class="pg-btn disabled">Кейинги &rsaquo;</span>
            <span class="pg-btn disabled">&raquo;</span>
        @endif

        <span class="pg-info">{{ $page }} / {{ $lastPage }} (Жами: {{ $total }})</span>
    </div>
    @endif

</div>
@endsection
