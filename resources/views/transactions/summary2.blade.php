@extends('layouts.app')

@section('title', 'АПЗ Шартномалар — План-Факт')

@push('styles')
<style>
    .report-container {
        background:#fff; border-radius:12px;
        box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #e8e8e8;
        padding:24px;
    }
    .report-header { text-align:center; margin-bottom:28px; padding-bottom:18px; border-bottom:2px solid #018c87; }
    .report-header h1 { font-size:1.2rem; font-weight:bold; margin-bottom:6px; color:#15191e; }
    .report-header h2 { font-size:1rem; font-weight:600; color:#018c87; margin-bottom:8px; }
    .report-header .date { font-size:.85rem; color:#6e788b; }

    .contracts-table { width:100%; border-collapse:collapse; font-size:.83rem; }
    .contracts-table thead th {
        padding:10px 10px; text-align:center; font-weight:600;
        color:#fff; background:#018c87; border:1px solid #017570; white-space:nowrap;
    }
    .contracts-table thead th:first-child { text-align:left; }
    .contracts-table tbody td { padding:9px 10px; border:1px solid #e4e4e4; }
    .contracts-table tbody tr:nth-child(even) { background:#f8fafb; }
    .contracts-table tbody tr:hover { background:#e8f4f3; }
    .contracts-table tbody td.r { text-align:right; }
    .contracts-table tbody td.c { text-align:center; }
    .total-row td { background:#e8f4f3 !important; font-weight:700; border-top:2px solid #018c87; }

    .status-badge {
        display:inline-block; padding:3px 9px; border-radius:6px;
        font-size:.72rem; font-weight:600; white-space:nowrap;
    }
    .status-active    { background:rgba(1,140,135,.1); color:#018c87; }
    .status-completed { background:rgba(6,184,56,.1);  color:#0a8a2e; }
    .status-cancelled { background:rgba(230,50,96,.1); color:#c02050; }

    .pbar-wrap { background:#e4e4e4; border-radius:3px; height:6px; min-width:60px; }
    .pbar      { height:6px; border-radius:3px; background:#018c87; }
    .pbar.over { background:#e63260; }

    .grand-stat { text-align:center; padding:16px 20px; border-radius:10px; border:1px solid #e0e0e0; }
    .grand-stat .val { font-size:1.5rem; font-weight:800; color:#018c87; }
    .grand-stat .lbl { font-size:.72rem; color:#6e788b; margin-top:4px; text-transform:uppercase; letter-spacing:.05em; }

    .print-btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:10px 22px; background:#018c87; color:#fff;
        border:none; border-radius:8px; font-size:.875rem; font-weight:600;
        cursor:pointer; transition:all .15s;
    }
    .print-btn:hover { background:#017570; }

    @media print {
        .platon-header,.platon-aside,.no-print { display:none !important; }
        .platon-main { margin-left:0 !important; }
        .report-container { box-shadow:none; border:none; }
    }
</style>
@endpush

@section('content')
<div class="report-container">

    {{-- Header --}}
    <div class="report-header">
        <h1>Тошкент шаҳри АПЗ шартномалари</h1>
        <h2>План — Факт ҳисоботи</h2>
        <div class="date">{{ now()->format('d.m.Y') }}</div>
    </div>

    {{-- Grand totals --}}
    <div class="row g-3 mb-4 no-print">
        <div class="col-md-3">
            <div class="grand-stat">
                <div class="val">{{ count($contracts) }}</div>
                <div class="lbl">Жами шартномалар</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat">
                <div class="val">{{ number_format($grandPlan / 1000000, 1, '.', ' ') }}</div>
                <div class="lbl">Жами план (млн.сўм)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat">
                <div class="val" style="color:#0a8a2e;">{{ number_format($grandFact / 1000000, 1, '.', ' ') }}</div>
                <div class="lbl">Жами факт (млн.сўм)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat">
                @php $overallPct = $grandPlan > 0 ? round($grandFact / $grandPlan * 100, 1) : 0; @endphp
                <div class="val" style="color:{{ $overallPct >= 100 ? '#0a8a2e' : '#d47000' }};">{{ $overallPct }}%</div>
                <div class="lbl">Умумий бажарилиш</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('summary2') }}" class="d-flex gap-2 mb-3 no-print" style="flex-wrap:wrap;">
        <select name="district" class="form-select form-select-sm" style="width:180px;" onchange="this.form.submit()">
            <option value="">Барча туман</option>
            @foreach($availableDistricts as $d)
            <option value="{{ $d }}" {{ $selectedDistrict === $d ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
        </select>
        <select name="status" class="form-select form-select-sm" style="width:160px;" onchange="this.form.submit()">
            <option value="">Барча ҳолат</option>
            <option value="active"   {{ $selectedStatus === 'active'    ? 'selected' : '' }}>Фаол</option>
            <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Якунлаган</option>
            <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Бекор қилинган</option>
        </select>
        @if($selectedDistrict || $selectedStatus)
        <a href="{{ route('summary2') }}" class="platon-btn platon-btn-outline platon-btn-sm">Тозалаш</a>
        @endif
        <button onclick="window.print()" type="button" class="print-btn" style="padding:6px 16px;font-size:.8rem;margin-left:auto;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <path d="M6 14h12v8H6z"/>
            </svg>
            Чоп
        </button>
    </form>

    {{-- Contracts table --}}
    <div class="table-responsive">
        <table class="contracts-table">
            <thead>
                <tr>
                    <th style="width:3%;">#</th>
                    <th style="text-align:left;width:20%;">Инвестор</th>
                    <th style="text-align:left;">Туман</th>
                    <th>Шартнома</th>
                    <th>Шартнома<br>санаси</th>
                    <th>Ҳолат</th>
                    <th>Шартнома<br>қиймати</th>
                    <th>Тўлов<br>шарти</th>
                    <th>Жадвал<br>сони</th>
                    <th>Факт тўлов</th>
                    <th>Бажарилиш</th>
                    <th>Прогресс</th>
                </tr>
            </thead>
            <tbody>
                {{-- Grand total row --}}
                <tr class="total-row">
                    <td class="c">—</td>
                    <td colspan="5" style="font-weight:700;">ЖАМИ ({{ count($contracts) }} шартнома)</td>
                    <td class="r">{{ number_format($grandPlan / 1000000, 2, '.', ' ') }}</td>
                    <td></td>
                    <td></td>
                    <td class="r">{{ number_format($grandFact / 1000000, 2, '.', ' ') }}</td>
                    <td class="r" style="color:{{ $grandPlan > $grandFact ? '#e63260' : '#0a8a2e' }};">
                        {{ number_format(($grandPlan - $grandFact) / 1000000, 2, '.', ' ') }}
                    </td>
                    <td>
                        <div class="pbar-wrap">
                            <div class="pbar {{ $grandPlan > 0 && $grandFact / $grandPlan > 1 ? 'over' : '' }}"
                                 style="width:{{ $grandPlan > 0 ? min(round($grandFact / $grandPlan * 100), 100) : 0 }}%;"></div>
                        </div>
                        <small style="font-size:.72rem;">{{ $grandPlan > 0 ? round($grandFact / $grandPlan * 100, 1) : 0 }}%</small>
                    </td>
                </tr>
                @foreach($contracts as $i => $c)
                @php
                    $plan    = (float) $c->contract_value;
                    $fact    = (float) $c->total_paid;
                    $balance = $plan - $fact;
                    $pct     = $plan > 0 ? round($fact / $plan * 100, 1) : 0;
                    $barW    = min($pct, 100);
                    $isOver  = $pct >= 100;

                    $statusClass = match(true) {
                        $c->contract_status === 'Yakunlagan'     => 'status-completed',
                        $c->contract_status === 'Bekor qilingan' => 'status-cancelled',
                        default                                  => 'status-active',
                    };
                    $statusLabel = match(true) {
                        $c->contract_status === 'Yakunlagan'     => 'Якунлаган',
                        $c->contract_status === 'Bekor qilingan' => 'Бекор қилинган',
                        default                                  => 'Фаол',
                    };
                @endphp
                <tr>
                    <td class="c" style="color:#aaa;font-size:.75rem;">{{ $i + 1 }}</td>
                    <td style="font-weight:500;font-size:.82rem;">{{ Str::limit($c->investor_name, 40) }}</td>
                    <td>{{ $c->district }}</td>
                    <td class="c" style="font-size:.78rem;color:#018c87;">{{ $c->contract_number }}</td>
                    <td class="c" style="font-size:.78rem;">{{ $c->contract_date ? \Carbon\Carbon::parse($c->contract_date)->format('d.m.Y') : '—' }}</td>
                    <td class="c">
                        <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="r">{{ $plan > 0 ? number_format($plan / 1000000, 2, '.', ' ') : '—' }}</td>
                    <td class="c" style="font-size:.75rem;">{{ $c->payment_terms ?: '—' }}</td>
                    <td class="c">{{ $c->installments_count ?: '—' }}</td>
                    <td class="r" style="color:#0a8a2e;font-weight:600;">{{ $fact > 0 ? number_format($fact / 1000000, 2, '.', ' ') : '—' }}</td>
                    <td class="r" style="color:{{ $balance <= 0 ? '#0a8a2e' : '#e63260' }};font-weight:600;font-size:.8rem;">
                        {{ $plan > 0 ? number_format($balance / 1000000, 2, '.', ' ') : '—' }}
                    </td>
                    <td style="min-width:70px;">
                        @if($plan > 0)
                        <div class="pbar-wrap">
                            <div class="pbar {{ $isOver ? 'over' : '' }}" style="width:{{ $barW }}%;"></div>
                        </div>
                        <small style="font-size:.7rem;">{{ $pct }}%</small>
                        @else
                        <small style="color:#bbb;">—</small>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
