@extends('layouts.app')

@section('title', 'Шартнома тафсилоти')

@push('styles')
<style>
    .report-wrap {
        background:#fff; border-radius:12px; border:1px solid #e8e8e8;
        box-shadow:0 1px 3px rgba(0,0,0,.08); padding:20px;
    }
    .report-head {
        display:flex; align-items:flex-start; justify-content:space-between;
        gap:12px; flex-wrap:wrap; margin-bottom:16px;
        border-bottom:1px solid #e8e8e8; padding-bottom:12px;
    }
    .report-head h1 { margin:0; font-size:1.1rem; font-weight:700; color:#15191e; }
    .report-head .sub { color:#6e788b; font-size:.8rem; margin-top:4px; }

    .stat-grid {
        display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
        gap:10px; margin-bottom:14px;
    }
    .stat {
        border:1px solid #e7ecec; border-radius:10px; padding:10px 12px;
        background:#f9fcfc;
    }
    .stat .lbl { font-size:.68rem; color:#6e788b; text-transform:uppercase; letter-spacing:.05em; }
    .stat .val { font-size:1rem; font-weight:700; color:#15191e; margin-top:4px; }

    .meta-grid {
        display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
        gap:10px; margin-bottom:16px;
    }
    .meta {
        border:1px solid #e7ecec; border-radius:10px; padding:10px 12px; background:#fff;
    }
    .meta .lbl { font-size:.68rem; color:#6e788b; text-transform:uppercase; letter-spacing:.05em; }
    .meta .val { margin-top:4px; font-weight:600; color:#1f2b43; font-size:.85rem; }

    .badge-mini { display:inline-block; padding:3px 8px; border-radius:8px; font-size:.72rem; font-weight:700; }
    .st-inprogress { background:rgba(1,140,135,.12); color:#018c87; }
    .st-completed  { background:rgba(6,184,56,.12); color:#0a8a2e; }
    .st-cancelled  { background:rgba(230,50,96,.12); color:#c02050; }
    .is-problem    { background:rgba(230,50,96,.12); color:#c02050; }
    .is-ok         { background:rgba(6,184,56,.12); color:#0a8a2e; }
    .is-unknown    { background:#f1f3f5; color:#7f8a9b; }

    .block-title { font-size:.86rem; font-weight:700; color:#015c58; margin:12px 0 8px; }
    .tbl-wrap { overflow-x:auto; border:1px solid #e7ecec; border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; font-size:.8rem; }
    .tbl thead th {
        background:#015c58; color:#fff; border:1px solid #014a47;
        padding:8px 8px; white-space:nowrap; text-align:center; font-size:.72rem;
    }
    .tbl tbody td { border:1px solid #e7ecec; padding:7px 8px; }
    .tbl tbody tr:nth-child(even) td { background:#f9fcfc; }
    .tbl td.r { text-align:right; }
    .tbl td.c { text-align:center; }
    .flow-in { color:#0a8a2e; font-weight:700; }
    .flow-out { color:#e63260; font-weight:700; }

    .pg-wrap { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:10px; flex-wrap:wrap; }
    .pg-btn {
        padding:5px 12px; border:1px solid #d0d0d0; border-radius:6px;
        background:#fff; color:#27314b; text-decoration:none; font-size:.78rem;
    }
    .pg-btn:hover { background:#f0f9f8; border-color:#018c87; color:#018c87; }
    .pg-btn.active { background:#018c87; border-color:#018c87; color:#fff; pointer-events:none; }
    .pg-btn.disabled { opacity:.4; pointer-events:none; }

    @media print {
        .platon-header,.platon-aside,.no-print { display:none !important; }
        .platon-main { margin-left:0 !important; }
        .report-wrap { box-shadow:none; border:none; }
    }
</style>
@endpush

@section('content')
<div class="report-wrap">
    <div class="report-head no-print">
        <div>
            <h1>Шартнома тафсилоти</h1>
            <div class="sub">№ {{ $contract['contract_number'] }} · {{ $contract['investor_name'] }}</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ $backUrl }}" class="platon-btn platon-btn-outline platon-btn-sm">← Орқага</a>
            <button type="button" onclick="window.print()" class="platon-btn platon-btn-primary platon-btn-sm">Чоп</button>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat"><div class="lbl">Шартнома қиймати</div><div class="val">{{ $contract['plan_mln'] }}</div></div>
        <div class="stat"><div class="lbl">Аванс</div><div class="val" style="color:#015c58;">{{ $contract['advance_label'] }}</div></div>
        <div class="stat"><div class="lbl">Факт тўлов</div><div class="val" style="color:#0a8a2e;">{{ $contract['fact_mln'] }}</div></div>
        <div class="stat"><div class="lbl">Қарздорлик (бугунгача)</div><div class="val" style="color:#e63260;">{{ $contract['debt_mln'] }}</div></div>
        <div class="stat"><div class="lbl">Бажарилиш</div><div class="val">{{ $contract['pct'] }}%</div></div>
    </div>

    <div class="meta-grid">
        <div class="meta"><div class="lbl">ID</div><div class="val">{{ $contract['contract_id'] }}</div></div>
        <div class="meta"><div class="lbl">Туман</div><div class="val">{{ $contract['district'] }}</div></div>
        <div class="meta"><div class="lbl">ИНН</div><div class="val">{{ $contract['inn'] }}</div></div>
        <div class="meta"><div class="lbl">Шартнома санаси</div><div class="val">{{ $contract['contract_date'] }}</div></div>
        <div class="meta"><div class="lbl">Ҳолат</div><div class="val"><span class="badge-mini {{ $contract['status_class'] }}">{{ $contract['status_label'] }}</span></div></div>
        <div class="meta"><div class="lbl">Қурилиш ҳолати</div><div class="val"><span class="badge-mini {{ $contract['issue_class'] }}">{{ $contract['issue_label'] }}</span></div></div>
    </div>

    <div class="block-title">Тўлов жадвали</div>
    <div class="tbl-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:6%">№</th>
                    <th>Тури</th>
                    <th>График санаси</th>
                    <th>График суммаси</th>
                    <th>Факт сумма</th>
                    <th>График ва факт фарқи</th>
                </tr>
            </thead>
            <tbody>
                @forelse($scheduleRows as $row)
                <tr>
                    <td class="c">{{ $row['row_num'] }}</td>
                    <td class="c">{{ $row['type'] }}</td>
                    <td class="c">{{ $row['date'] }}</td>
                    <td class="r">{{ $row['schedule_amount'] }}</td>
                    <td class="r">{{ $row['fact_amount'] }}</td>
                    <td class="r {{ $row['diff_class'] }}">{{ $row['diff_amount'] }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="c" style="color:#8892a5;">Жадвал топилмади</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="block-title">Тўловлар ({{ number_format($total) }})</div>
    <div class="tbl-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:6%">№</th>
                    <th>Сана</th>
                    <th>Тур</th>
                    <th>Оқим</th>
                    <th>Сўм</th>
                    <th style="text-align:left">Мақсад</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td class="c">{{ $payment['row_num'] }}</td>
                    <td class="c">{{ $payment['payment_date'] }}</td>
                    <td class="c">{{ $payment['type'] }}</td>
                    <td class="c {{ $payment['flow_class'] }}">{{ $payment['flow'] }}</td>
                    <td class="r {{ $payment['flow_class'] }}">{{ $payment['amount_signed_mln'] }}</td>
                    <td>{{ $payment['purpose'] }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="c" style="color:#8892a5;">Тўловлар топилмади</td></tr>
                @endforelse
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
    </div>
    @endif
</div>
@endsection
