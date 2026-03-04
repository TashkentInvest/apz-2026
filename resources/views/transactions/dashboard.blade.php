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

    $maxDistrict = count($districtStats) ? max(array_column((array) $districtStats, 'income')) : 1;
    $maxDistrict = $maxDistrict ?: 1;
    $maxType     = count($typeStats) ? max(array_column((array) $typeStats, 'income')) : 1;
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

{{-- ── Two-column: Monthly + Districts ── --}}
<div class="two-col">
    {{-- Monthly Table --}}
    <div class="tbl-block">
        <div class="tbl-block-header">
            Ойлик тушум
            <span class="sub">Охирги 18 ой</span>
        </div>
        <div style="overflow-x:auto;max-height:400px;overflow-y:auto;">
            <table class="inline-table">
                <thead>
                    <tr>
                        <th>Йил</th>
                        <th>Ой</th>
                        <th class="num">Приход (млн)</th>
                        <th class="num">Сони</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyStats as $stat)
                        <tr class="clickable" onclick="openModal('Ой: '+this.dataset.month+' '+this.dataset.year, {year:this.dataset.year, month:this.dataset.month})" data-year="{{ $stat->year }}" data-month="{{ $stat->month }}">
                            <td><span class="month-badge">{{ $stat->year }}</span></td>
                            <td class="name">{{ $stat->month }}</td>
                            <td class="num">{{ number_format($stat->income, 2, '.', ' ') }}</td>
                            <td class="cnt">{{ number_format($stat->cnt) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:30px;color:#aab0bb;">Маълумот йўқ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- District Table --}}
    <div class="tbl-block">
        <div class="tbl-block-header">
            Туманлар бўйича
            <span class="sub">Приход бўйича</span>
        </div>
        <div style="overflow-x:auto;max-height:400px;overflow-y:auto;">
            <table class="inline-table">
                <thead>
                    <tr>
                        <th>Туман</th>
                        <th class="num">Приход (млн)</th>
                        <th class="num">Сони</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($districtStats as $stat)
                        @php $pct = $maxDistrict > 0 ? ($stat->income / $maxDistrict * 100) : 0; @endphp
                        <tr class="clickable" onclick="openModal('Туман: '+this.dataset.district, {district:this.dataset.district})" data-district="{{ $stat->district }}">
                            <td class="name">
                                {{ $stat->district }}
                                <div class="bar-wrap"><div class="bar-fill" style="width:{{ $pct }}%"></div></div>
                            </td>
                            <td class="num">{{ number_format($stat->income, 1, '.', ' ') }}</td>
                            <td class="cnt">{{ number_format($stat->cnt) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;padding:30px;color:#aab0bb;">Маълумот йўқ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Plan vs Fact by district ── --}}
<div class="tbl-block">
    <div class="tbl-block-header">
        План — Факт (Туманлар бўйича)
        <span class="sub">Шартнома қиймати vs Тушум (млн.сўм)</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="inline-table">
            <thead>
                <tr>
                    <th>Туман</th>
                    <th class="num">План</th>
                    <th class="num">Факт</th>
                    <th class="num">Бажарилиш</th>
                    <th style="width:160px;">Прогресс</th>
                </tr>
            </thead>
            <tbody>
                @forelse($planFact as $pf)
                @php
                    $pct = $pf->plan_total > 0 ? min(round($pf->fact_total / $pf->plan_total * 100), 100) : 0;
                    $pctReal = $pf->plan_total > 0 ? round($pf->fact_total / $pf->plan_total * 100, 1) : 0;
                @endphp
                <tr>
                    <td class="name">{{ $pf->district }}</td>
                    <td class="num">{{ number_format($pf->plan_total, 1, '.', ' ') }}</td>
                    <td class="num" style="color:#0a8a2e;">{{ number_format($pf->fact_total, 1, '.', ' ') }}</td>
                    <td class="num" style="color:{{ $pf->plan_total > $pf->fact_total ? '#e63260' : '#0a8a2e' }};">
                        {{ number_format($pf->plan_total - $pf->fact_total, 1, '.', ' ') }}
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div class="bar-wrap" style="flex:1;"><div class="bar-fill" style="width:{{ $pct }}%;"></div></div>
                            <span style="font-size:.72rem;color:#6e788b;white-space:nowrap;">{{ $pctReal }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:#aab0bb;">Маълумот йўқ</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Payment types ── --}}
<div class="tbl-block">
    <div class="tbl-block-header">
        Тўлов турлари бўйича
        <span class="sub">АПЗ / Пеня / Қайтариш</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="inline-table">
            <thead>
                <tr>
                    <th>Тури</th>
                    <th class="num">Приход (млн)</th>
                    <th class="num">Сони</th>
                    <th style="width:160px;">Улуш</th>
                </tr>
            </thead>
            <tbody>
                @forelse($typeStats as $stat)
                    @php $pct = $maxType > 0 ? ($stat->income / $maxType * 100) : 0; @endphp
                    <tr>
                        <td class="name">{{ $stat->type ?? '—' }}</td>
                        <td class="num">{{ number_format($stat->income, 2, '.', ' ') }}</td>
                        <td class="cnt">{{ number_format($stat->cnt) }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="bar-wrap" style="flex:1;"><div class="bar-fill" style="width:{{ $pct }}%;background:#1471f0;"></div></div>
                                <span style="font-size:.72rem;color:#6e788b;white-space:nowrap;">{{ number_format($pct, 0) }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:#aab0bb;">Маълумот йўқ</td></tr>
                @endforelse
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