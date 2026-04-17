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
    .formula-wrap { border:1px solid #e7ecec; border-radius:10px; padding:10px 12px; background:#fcfefe; margin-bottom:14px; }
    .formula-grid {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
        gap:10px;
    }
    .formula-card {
        border:1px solid #dfe9ea;
        border-radius:10px;
        background:#fff;
        padding:10px 12px;
    }
    .formula-card .t { font-size:.73rem; color:#6e788b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
    .formula-card .k { font-size:.8rem; color:#1f2b43; line-height:1.45; }
    .formula-card .v { margin-top:8px; font-size:.94rem; font-weight:700; color:#015c58; }
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
    .schedule-edit-wrap {
        border:1px solid #e7ecec;
        border-radius:10px;
        padding:10px;
        background:#fbfefe;
        margin:10px 0 14px;
    }
    .schedule-input {
        width:100%;
        border:1px solid #d8e0e6;
        border-radius:6px;
        padding:6px 8px;
        font-size:.8rem;
    }
    .schedule-actions {
        display:flex;
        justify-content:flex-end;
        gap:8px;
        margin-top:10px;
        flex-wrap:wrap;
    }
    .flow-in { color:#0a8a2e; font-weight:700; }
    .flow-out { color:#e63260; font-weight:700; }
    .pct-red { color:#e63260; font-weight:700; }
    .pct-orange { color:#ef7d00; font-weight:700; }
    .pct-yellow { color:#b58900; font-weight:700; }
    .pct-green { color:#0a8a2e; font-weight:700; }
    .pct-none { color:#8892a5; font-weight:600; }

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
    @if(session('success'))
        <div class="platon-alert platon-alert-success no-print" style="margin-bottom:12px;">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="platon-alert platon-alert-danger no-print" style="margin-bottom:12px;">✗ {{ session('error') }}</div>
    @endif

    <div class="report-head no-print">
        <div>
            <h1>Шартнома тафсилоти</h1>
            <div class="sub">№ {{ $contract['contract_number'] }} · {{ $contract['investor_name'] }}</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ $backUrl }}" class="platon-btn platon-btn-outline platon-btn-sm">← Орқага</a>
            @if($canEditSchedule)
                <button type="button" id="schedule-edit-toggle" class="platon-btn platon-btn-outline platon-btn-sm">Тўлов жадвалини таҳрирлаш</button>
            @endif
            <button type="button" onclick="window.print()" class="platon-btn platon-btn-primary platon-btn-sm">Чоп</button>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat"><div class="lbl">Шартнома қиймати</div><div class="val">{{ $contract['plan_mln'] }}</div></div>
        <div class="stat"><div class="lbl">Аванс</div><div class="val" style="color:#015c58;">{{ $contract['advance_label'] }}</div></div>
        <div class="stat"><div class="lbl">Факт тўлов</div><div class="val" style="color:#0a8a2e;">{{ $contract['fact_mln'] }}</div></div>
        <div class="stat"><div class="lbl">Факт тўлов (аванссиз)</div><div class="val" style="color:#0a8a2e;">{{ $contract['fact_without_advance_mln'] }}</div></div>
        <div class="stat"><div class="lbl">Қолдиқ қиймат</div><div class="val" style="color:#e63260;">{{ $contract['qoldiq_mln'] }}</div></div>
    </div>

    <div class="formula-wrap">
        <div class="block-title" style="margin-top:0;">Ҳисоблаш босқичлари</div>
        <div class="formula-grid">
            <div class="formula-card">
                <div class="t">1-блок</div>
                <div class="k">Қолдиқ қиймат = (Шартнома қиймати - Аванс - Факт тўлов) - (Шартнома қиймати - Факт тўлов)</div>
                <div class="v">= {{ $contract['qoldiq_mln'] }}</div>
            </div>
            <div class="formula-card">
                <div class="t">2-блок</div>
                <div class="k">Шартнома қиймати - Факт тўлов</div>
                <div class="v">= {{ $contract['qoldiq_mln'] }}</div>
            </div>
            <div class="formula-card">
                <div class="t">3-блок</div>
                <div class="k">График тўлов (сана &lt; бугун) - АВАНС = График тўлов</div>
                <div class="v">= {{ $contract['plan_due_today_mln'] }}</div>
            </div>
            <div class="formula-card">
                <div class="t">4-блок</div>
                <div class="k">График факт тўлов (Факт тўлов - Аванс)</div>
                <div class="v">= {{ $contract['fact_without_advance_mln'] }}</div>
            </div>
            <div class="formula-card">
                <div class="t">5-блок</div>
                <div class="k">Қарздорлик = (График тўлов (сана &lt; бугун) - АВАНС) - График факт тўлов</div>
                <div class="v">= {{ $contract['debt_mln'] }}</div>
            </div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta"><div class="lbl">ID</div><div class="val">{{ $contract['contract_id'] }}</div></div>
        <div class="meta"><div class="lbl">Туман</div><div class="val">{{ $contract['district'] }}</div></div>
        <div class="meta"><div class="lbl">МФЙ</div><div class="val">{{ $contract['mfy'] }}</div></div>
        <div class="meta"><div class="lbl">Манзил</div><div class="val">{{ $contract['address'] }}</div></div>
        <div class="meta"><div class="lbl">Қурилиш ҳажми</div><div class="val">{{ $contract['build_volume'] }}</div></div>
        <div class="meta"><div class="lbl">Коэффицент</div><div class="val">{{ $contract['coefficient'] }}</div></div>
        <div class="meta"><div class="lbl">Зона</div><div class="val">{{ $contract['zone'] }}</div></div>
        <div class="meta"><div class="lbl">Рухсатнома</div><div class="val">{{ $contract['permit'] }}</div></div>
        <div class="meta"><div class="lbl">АПЗ номер</div><div class="val">{{ $contract['apz_number'] }}</div></div>
        <div class="meta"><div class="lbl">Кенгаш хулосаси</div><div class="val">{{ $contract['council_decision'] }}</div></div>
        <div class="meta"><div class="lbl">Экспертиза хулосаси</div><div class="val">{{ $contract['expertise'] }}</div></div>
        <div class="meta"><div class="lbl">ИНН</div><div class="val">{{ $contract['inn'] }}</div></div>
        <div class="meta"><div class="lbl">Шартнома санаси</div><div class="val">{{ $contract['contract_date'] }}</div></div>
        <div class="meta"><div class="lbl">Ҳолат</div><div class="val"><span class="badge-mini {{ $contract['status_class'] }}">{{ $contract['status_label'] }}</span></div></div>
        <div class="meta"><div class="lbl">Қурилиш ҳолати</div><div class="val"><span class="badge-mini {{ $contract['issue_class'] }}">{{ $contract['issue_label'] }}</span></div></div>
    </div>

    <div class="block-title">Тўлов жадвали</div>

    @if($canEditSchedule)
        @php
            $scheduleEditorInputRows = old('schedule');
            if (!is_array($scheduleEditorInputRows) || empty($scheduleEditorInputRows)) {
                $scheduleEditorInputRows = array_map(static function ($row) {
                    return [
                        'date' => (string) ($row['date'] ?? ''),
                        'amount' => (string) ($row['amount'] ?? ''),
                    ];
                }, $scheduleEditorRows ?? []);
            }
        @endphp

        <div id="schedule-edit-wrap" class="schedule-edit-wrap no-print" style="display:none;">
            <form method="POST" action="{{ route('contracts.schedule.update', ['contractId' => $contract['contract_id']]) }}">
                @csrf
                <input type="hidden" name="back" value="{{ request('back', $backUrl) }}">
                <input type="hidden" name="page" value="{{ request('page', 1) }}">

                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th style="width:6%;">№</th>
                                <th>График санаси</th>
                                <th>График суммаси</th>
                                <th style="width:10%;">Амал</th>
                            </tr>
                        </thead>
                        <tbody id="schedule-editor-body">
                            @forelse($scheduleEditorInputRows as $index => $editRow)
                                <tr>
                                    <td class="c js-row-num">{{ $index + 1 }}</td>
                                    <td>
                                        <input type="date" class="schedule-input" name="schedule[{{ $index }}][date]" value="{{ (string) ($editRow['date'] ?? '') }}">
                                    </td>
                                    <td>
                                        <input type="number" class="schedule-input" step="0.01" min="0" name="schedule[{{ $index }}][amount]" value="{{ (string) ($editRow['amount'] ?? '') }}" placeholder="0.00">
                                    </td>
                                    <td class="c">
                                        <button type="button" class="platon-btn platon-btn-outline platon-btn-sm js-remove-schedule-row">Ўчириш</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="c js-row-num">1</td>
                                    <td><input type="date" class="schedule-input" name="schedule[0][date]"></td>
                                    <td><input type="number" class="schedule-input" step="0.01" min="0" name="schedule[0][amount]" placeholder="0.00"></td>
                                    <td class="c"><button type="button" class="platon-btn platon-btn-outline platon-btn-sm js-remove-schedule-row">Ўчириш</button></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="schedule-actions">
                    <button type="button" id="add-schedule-row" class="platon-btn platon-btn-outline platon-btn-sm">+ Қатор қўшиш</button>
                    <button type="submit" class="platon-btn platon-btn-primary platon-btn-sm">Сақлаш</button>
                </div>
            </form>
        </div>
    @endif

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
                    <th>%</th>
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
                    <td class="c {{ $row['diff_pct_class'] }}">{{ $row['diff_pct'] }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="c" style="color:#8892a5;">Жадвал топилмади</td></tr>
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

<script>
    (function () {
        const toggleBtn = document.getElementById('schedule-edit-toggle');
        const wrap = document.getElementById('schedule-edit-wrap');
        const body = document.getElementById('schedule-editor-body');
        const addBtn = document.getElementById('add-schedule-row');
        const openByDefault = @json(session('error') || is_array(old('schedule')));

        if (!toggleBtn || !wrap || !body || !addBtn) {
            return;
        }

        const renumberRows = () => {
            const rows = Array.from(body.querySelectorAll('tr'));

            rows.forEach((row, index) => {
                const numCell = row.querySelector('.js-row-num');
                if (numCell) {
                    numCell.textContent = String(index + 1);
                }

                const dateInput = row.querySelector('input[type="date"]');
                const amountInput = row.querySelector('input[type="number"]');

                if (dateInput) {
                    dateInput.name = `schedule[${index}][date]`;
                }
                if (amountInput) {
                    amountInput.name = `schedule[${index}][amount]`;
                }
            });
        };

        const addRow = (dateValue = '', amountValue = '') => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="c js-row-num"></td>
                <td><input type="date" class="schedule-input" value="${dateValue}"></td>
                <td><input type="number" class="schedule-input" step="0.01" min="0" value="${amountValue}" placeholder="0.00"></td>
                <td class="c"><button type="button" class="platon-btn platon-btn-outline platon-btn-sm js-remove-schedule-row">Ўчириш</button></td>
            `;
            body.appendChild(row);
            renumberRows();
        };

        toggleBtn.addEventListener('click', function () {
            const isHidden = wrap.style.display === 'none';
            wrap.style.display = isHidden ? 'block' : 'none';
        });

        if (openByDefault) {
            wrap.style.display = 'block';
        }

        addBtn.addEventListener('click', function () {
            addRow();
        });

        body.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.js-remove-schedule-row');
            if (!removeBtn) {
                return;
            }

            const rows = body.querySelectorAll('tr');
            if (rows.length <= 1) {
                const dateInput = rows[0].querySelector('input[type="date"]');
                const amountInput = rows[0].querySelector('input[type="number"]');
                if (dateInput) dateInput.value = '';
                if (amountInput) amountInput.value = '';
                return;
            }

            const row = removeBtn.closest('tr');
            if (row) {
                row.remove();
                renumberRows();
            }
        });

        renumberRows();
    })();
</script>
@endsection
