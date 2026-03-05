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

    .issue-badge {
        display:inline-block; padding:3px 9px; border-radius:6px;
        font-size:.7rem; font-weight:600; white-space:nowrap;
    }
    .issue-problem   { background:rgba(230,50,96,.1); color:#c02050; }
    .issue-ok        { background:rgba(6,184,56,.1); color:#0a8a2e; }
    .issue-unknown   { background:#f1f3f5; color:#7f8a9b; }
    .txt-good { color:#0a8a2e; }
    .txt-warn { color:#d47000; }
    .txt-danger { color:#e63260; }
    .link-clean { color:#018c87; text-decoration:none; }

    .pbar-wrap { background:#e4e4e4; border-radius:3px; height:6px; min-width:60px; }
    .pbar      { height:6px; border-radius:3px; background:#018c87; }
    .pbar.over { background:#e63260; }

    /* ── Pagination ── */
    .pg-wrap { display:flex; align-items:center; justify-content:center; gap:6px; flex-wrap:wrap; margin-top:16px; }
    .pg-btn {
        padding:5px 14px; border:1px solid #d0d0d0; border-radius:6px;
        background:#fff; color:#27314b; font-size:.8rem; cursor:pointer; text-decoration:none;
    }
    .pg-btn:hover { background:#f0f9f8; border-color:#018c87; color:#018c87; }
    .pg-btn.active { background:#018c87; border-color:#018c87; color:#fff; font-weight:700; pointer-events:none; }
    .pg-btn:disabled, .pg-btn.disabled { opacity:.4; pointer-events:none; }
    .pg-info { font-size:.78rem; color:#6e788b; }

    /* ── Contract detail modal ── */
    .contracts-table tbody tr.clickable { cursor:pointer; }
    .contracts-table tbody tr.clickable:hover td { background:#e2f6f5 !important; }
    #contract-modal {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
        z-index:1000; align-items:center; justify-content:center; padding:16px;
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
    .flow-in  { color:#0a8a2e; font-weight:600; }
    .flow-out { color:#e63260; font-weight:600; }

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
        <div class="date">{{ $reportDate }}</div>
    </div>

    {{-- Grand totals --}}
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
                <div class="lbl">Жами план (млн.сўм)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat">
                <div class="val txt-good">{{ $summaryStats['grand_fact_mln'] }}</div>
                <div class="lbl">Жами факт (млн.сўм)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="grand-stat">
                <div class="val {{ $summaryStats['overall_pct_class'] }}">{{ $summaryStats['overall_pct'] }}%</div>
                <div class="lbl">Умумий бажарилиш</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('summary2') }}" class="d-flex gap-2 mb-3 no-print" style="flex-wrap:wrap;">
        <select name="district" class="form-select form-select-sm" style="width:180px;" onchange="this.form.submit()">
            @foreach($districtOptions as $option)
            <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <select name="status" class="form-select form-select-sm" style="width:180px;" onchange="this.form.submit()">
            @foreach($statusOptions as $option)
            <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <select name="issue" class="form-select form-select-sm" style="width:190px;" onchange="this.form.submit()">
            @foreach($issueOptions as $option)
            <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <input type="text" name="search" value="{{ $searchTerm }}" class="form-control form-control-sm" style="width:240px;" placeholder="Компания / шартнома / ИНН / ID">
        <button type="submit" class="platon-btn platon-btn-outline platon-btn-sm">Қидириш</button>
        @if($showResetFilters)
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
                    <th>Қурилиш<br>ҳолати</th>
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
                    <td colspan="6" style="font-weight:700;">ЖАМИ ({{ $total }} шартнома)</td>
                    <td class="r">{{ $summaryRow['plan_mln'] }}</td>
                    <td></td>
                    <td></td>
                    <td class="r">{{ $summaryRow['fact_mln'] }}</td>
                    <td class="r {{ $summaryRow['balance_class'] }}">{{ $summaryRow['balance_mln'] }}</td>
                    <td>
                        <div class="pbar-wrap">
                            <div class="pbar {{ $summaryRow['progress_class'] }}" style="width:{{ $summaryRow['progress_width'] }}%;"></div>
                        </div>
                        <small style="font-size:.72rem;">{{ $summaryRow['progress_label'] }}</small>
                    </td>
                </tr>
                @foreach($contracts as $contract)
                <tr class="clickable" onclick="window.location='{{ $contract['detail_url'] }}'">
                    <td class="c" style="color:#aaa;font-size:.75rem;">{{ $contract['row_num'] }}</td>
                    <td style="font-weight:500;font-size:.82rem;">{{ $contract['investor_name'] }}</td>
                    <td>{{ $contract['district'] }}</td>
                    <td class="c" style="font-size:.78rem;"><a href="{{ $contract['detail_url'] }}" class="link-clean" onclick="event.stopPropagation()">{{ $contract['contract_number'] }}</a></td>
                    <td class="c" style="font-size:.78rem;">{{ $contract['contract_date'] }}</td>
                    <td class="c">
                        <span class="status-badge {{ $contract['status_class'] }}">{{ $contract['status_label'] }}</span>
                    </td>
                    <td class="c">
                        <span class="issue-badge {{ $contract['issue_class'] }}">{{ $contract['issue_label'] }}</span>
                    </td>
                    <td class="r">{{ $contract['plan_mln'] }}</td>
                    <td class="c" style="font-size:.75rem;">{{ $contract['payment_terms'] }}</td>
                    <td class="c">{{ $contract['installments_count'] }}</td>
                    <td class="r txt-good" style="font-weight:600;">{{ $contract['fact_mln'] }}</td>
                    <td class="r {{ $contract['balance_class'] }}" style="font-weight:600;font-size:.8rem;">{{ $contract['balance_mln'] }}</td>
                    <td style="min-width:70px;">
                        @if($contract['progress_show'])
                        <div class="pbar-wrap">
                            <div class="pbar {{ $contract['progress_class'] }}" style="width:{{ $contract['progress_width'] }}%;"></div>
                        </div>
                        <small style="font-size:.7rem;">{{ $contract['progress_label'] }}</small>
                        @else
                        <small style="color:#bbb;">—</small>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($lastPage > 1)
    <div class="pg-wrap">
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

{{-- Contract Detail Modal --}}
<div id="contract-modal">
    <div class="cm-box">
        <div class="cm-head">
            <h3 id="cm-title">Шартнома тафсилоти</h3>
            <button onclick="closeContract()">✕</button>
        </div>
        <div class="cm-body">
            <div id="cm-meta" class="cm-meta"></div>

            <div class="cm-section-title">Тўлов жадвали</div>
            <div style="overflow-x:auto;">
                <table class="cm-table" id="cm-sched-table">
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
            <span id="cm-pg-info" class="pg-info">—</span>
            <button id="cm-next" class="cm-pg-btn" disabled>Кейинги →</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CONTRACT_URL = '{{ url('/modal/contract') }}';
let cmState = { contractId: null, page: 1, lastPage: 1 };

function openContract(contractId, title) {
    cmState = { contractId, page: 1, lastPage: 1 };
    document.getElementById('cm-title').textContent = title;
    document.getElementById('contract-modal').classList.add('open');
    document.getElementById('cm-meta').innerHTML = '<div style="color:#aaa;text-align:center;padding:20px;">Юкланмоқда...</div>';
    document.getElementById('cm-sched-body').innerHTML = '<tr><td colspan="3" style="text-align:center;color:#aaa;padding:12px;">...</td></tr>';
    document.getElementById('cm-pay-body').innerHTML = '<tr><td colspan="6" style="text-align:center;color:#aaa;padding:12px;">...</td></tr>';
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
    info.textContent = 'Юкланмоқда...';

    fetch(`${CONTRACT_URL}/${cmState.contractId}?page=${cmState.page}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            document.getElementById('cm-meta').innerHTML = `<div style="color:#e63260;padding:20px;">${data.error}</div>`;
            return;
        }
        cmState.lastPage = data.last_page;
        renderContractMeta(data.contract);
        renderSchedule(data.schedule);
        renderPayments(data.payments, data.page, data.per_page);
        info.textContent = `${data.page} / ${data.last_page} (Жами: ${data.total})`;
        prev.disabled = data.page <= 1;
        next.disabled = data.page >= data.last_page;
    })
    .catch(err => {
        document.getElementById('cm-meta').innerHTML = '<div style="color:#e63260;padding:20px;">Хатолик юз берди</div>';
        console.error(err);
    });
}

function renderContractMeta(c) {
    const fmt = v => v ? Number(v).toLocaleString('ru') : '—';
    const fmtM = v => v > 0 ? (v / 1000000).toFixed(4) + ' млн' : '—';
    const meta = [
        { lbl: 'Шартнома рақами',  val: c.contract_number || '—' },
        { lbl: 'Туман',           val: c.district || '—' },
        { lbl: 'Инвестор',        val: c.investor_name || '—' },
        { lbl: 'Шартнома санаси', val: c.contract_date ? c.contract_date.slice(0,10).split('-').reverse().join('.') : '—' },
        { lbl: 'Шартнома қиймати (млн)', val: fmtM(c.contract_value) },
        { lbl: 'Жами тўланган (млн)',    val: fmtM(c.total_paid) },
        { lbl: 'Тўловлар сони',   val: c.payment_count || '0' },
        { lbl: 'Тўлов шарти',     val: c.payment_terms || '—' },
        { lbl: 'Бўлиб тўлаш',     val: c.installments_count ? c.installments_count + ' та' : '—' },
        { lbl: 'Ҳолат',           val: c.contract_status || '—' },
    ];
    document.getElementById('cm-meta').innerHTML = meta.map(m =>
        `<div class="cm-meta-item"><div class="lbl">${m.lbl}</div><div class="val">${m.val}</div></div>`
    ).join('');
}

function renderSchedule(schedule) {
    const tbody = document.getElementById('cm-sched-body');
    if (!schedule || !schedule.length) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#aaa;padding:12px;">Жадвал йўқ</td></tr>';
        return;
    }
    tbody.innerHTML = schedule.map((s, i) =>
        `<tr><td style="text-align:center;color:#aaa;font-size:.72rem;">${i+1}</td>
             <td>${s.date}</td>
             <td class="r">${Number(s.amount).toFixed(4)}</td></tr>`
    ).join('');
}

function renderPayments(payments, page, perPage) {
    const tbody = document.getElementById('cm-pay-body');
    if (!payments || !payments.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#aaa;padding:12px;">Тўловлар йўқ</td></tr>';
        return;
    }
    tbody.innerHTML = payments.map((p, i) => {
        const isIn = p.flow === 'Приход';
        const amtM = (Number(p.amount) / 1000000).toFixed(4);
        return `<tr>
            <td style="text-align:center;color:#aaa;font-size:.72rem;">${(page-1)*perPage + i + 1}</td>
            <td>${p.payment_date || '—'}</td>
            <td style="font-size:.75rem;">${p.type || '—'}</td>
            <td class="${isIn ? 'flow-in' : 'flow-out'}">${p.flow || '—'}</td>
            <td class="r ${isIn ? 'flow-in' : 'flow-out'}">${isIn ? '+' : '-'}${amtM}</td>
            <td style="font-size:.72rem;color:#888;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${p.payment_purpose || ''}">${p.payment_purpose || '—'}</td>
        </tr>`;
    }).join('');
}

document.getElementById('cm-prev').onclick = () => { if(cmState.page > 1){ cmState.page--; fetchContract(); } };
document.getElementById('cm-next').onclick = () => { if(cmState.page < cmState.lastPage){ cmState.page++; fetchContract(); } };
document.getElementById('contract-modal').addEventListener('click', e => { if(e.target === e.currentTarget) closeContract(); });
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeContract(); });
</script>
@endpush
