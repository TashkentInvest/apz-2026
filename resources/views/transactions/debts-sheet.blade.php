<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $debtTypeLabel }} — {{ $reportDate }}</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; background: #f5f7fa; color: #1a1a2e; }

    .toolbar {
        position: sticky; top: 0; z-index: 100;
        background: #018c87; color: #fff;
        display: flex; align-items: center; gap: 10px;
        padding: 10px 18px; box-shadow: 0 2px 8px rgba(0,0,0,.2);
        flex-wrap: wrap;
    }
    .toolbar h2 { font-size: 1rem; font-weight: 700; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .toolbar .date { font-size: .78rem; opacity: .85; white-space: nowrap; }
    .btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 14px; border-radius: 6px; border: none;
        font-size: .78rem; font-weight: 600; cursor: pointer; text-decoration: none;
        white-space: nowrap; transition: background .15s;
    }
    .btn-white { background: #fff; color: #018c87; }
    .btn-white:hover { background: #e0f5f4; }
    .btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.6); color: #fff; }
    .btn-outline:hover { background: rgba(255,255,255,.15); }

    .summary-bar {
        display: flex; gap: 12px; flex-wrap: wrap;
        padding: 12px 18px; background: #fff;
        border-bottom: 1px solid #dce3e8;
    }
    .stat { text-align: center; padding: 8px 14px; border-radius: 8px; border: 1px solid #e0e0e0; min-width: 160px; }
    .stat .val { font-size: 1rem; font-weight: 800; color: #018c87; }
    .stat.debt .val { color: #c62828; }
    .stat .lbl { font-size: .7rem; color: #6e788b; text-transform: uppercase; letter-spacing: .04em; margin-top: 3px; }

    .tbl-wrap { overflow: auto; padding: 0 18px 60px; }

    table#sheet {
        border-collapse: collapse;
        min-width: 100%;
        font-size: 12.5px;
        background: #fff;
        margin-top: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        border-radius: 8px;
        overflow: hidden;
    }
    table#sheet thead th {
        background: #018c87; color: #fff;
        padding: 9px 8px; text-align: center;
        font-weight: 600; white-space: nowrap;
        border: 1px solid #00706c; position: sticky; top: 48px;
    }
    table#sheet tbody td {
        padding: 7px 8px; border: 1px solid #e4e4e4; vertical-align: middle;
    }
    table#sheet tbody tr:nth-child(even) { background: #f8fafa; }
    table#sheet tbody tr:hover { background: #e4f4f3; }
    table#sheet .r { text-align: right; font-variant-numeric: tabular-nums; }
    table#sheet .c { text-align: center; }

    .total-row td { background: #e0f4f3 !important; font-weight: 700; border-top: 2px solid #018c87; }
    .debt-red  { color: #c62828; font-weight: 600; }
    .debt-soft { color: #d84343; font-weight: 600; }
    .txt-good  { color: #0a8a2e; }

    @media print {
        .toolbar, .summary-bar { position: static !important; }
        .btn { display: none !important; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <h2>{{ $debtTypeLabel }}</h2>
    <span class="date">{{ $reportDate }}</span>
    <button class="btn btn-white" onclick="downloadXlsx()">⬇ XLSX юклаш</button>
    <button class="btn btn-outline" onclick="copyTable()">📋 Нусха кўчириш</button>
    <a href="{{ $backUrl }}" class="btn btn-outline">← Орқага</a>
    <button class="btn btn-outline" onclick="window.print()">🖨 Чоп</button>
</div>

<div class="summary-bar">
    <div class="stat"><div class="val">{{ $total }}</div><div class="lbl">Шартномалар</div></div>
    <div class="stat"><div class="val">{{ $grandPlan }}</div><div class="lbl">Шартнома қиймати (сўм)</div></div>
    <div class="stat"><div class="val txt-good">{{ $grandFact }}</div><div class="lbl">Факт тўлов (сўм)</div></div>
    <div class="stat debt"><div class="val debt-red">{{ $grandDebt }}</div><div class="lbl">Муддати ўтган қарз (сўм)</div></div>
    <div class="stat debt"><div class="val debt-soft">{{ $grandUnoverdueDebt }}</div><div class="lbl">Муддати келмаган қарз (сўм)</div></div>
    <div class="stat debt"><div class="val debt-red">{{ $grandTotalDebt }}</div><div class="lbl">Жами қарздорлик (сўм)</div></div>
</div>

<div class="tbl-wrap">
<table id="sheet">
    <thead>
        <tr>
            <th style="width:40px;">№</th>
            <th>Компания номи</th>
            <th>Туман</th>
            <th>Шартнома рақами</th>
            <th>Шартнома санаси</th>
            <th>Ҳолат</th>
            <th>Қурилиш ҳолати</th>
            <th>Шартнома қиймати</th>
            <th>Факт тўлаган</th>
            <th>Муддати ўтган қарз</th>
            <th>Муддати келмаган қарз</th>
            <th>План-Факт фарқи</th>
        </tr>
    </thead>
    <tbody>
        <tr class="total-row">
            <td class="c">—</td>
            <td colspan="6">ЖАМИ ({{ $total }} шартнома)</td>
            <td class="r">{{ $grandPlan }}</td>
            <td class="r txt-good">{{ $grandFact }}</td>
            <td class="r debt-red">{{ $grandDebt }}</td>
            <td class="r debt-soft">{{ $grandUnoverdueDebt }}</td>
            <td class="r">{{ $grandTotalDebt }}</td>
        </tr>
        @foreach($contracts as $c)
        <tr>
            <td class="c">{{ $c['row_num'] }}</td>
            <td><a href="{{ $c['detail_url'] }}" target="_blank" style="color:#018c87;text-decoration:none;">{{ $c['investor_name'] }}</a></td>
            <td class="c">{{ $c['district'] }}</td>
            <td class="c">{{ $c['contract_number'] }}</td>
            <td class="c">{{ $c['contract_date'] }}</td>
            <td class="c">{{ $c['status_label'] }}</td>
            <td class="c">{{ $c['issue_label'] }}</td>
            <td class="r">{{ $c['plan_mln'] }}</td>
            <td class="r txt-good">{{ $c['fact_mln'] }}</td>
            <td class="r debt-red">{{ $c['debt_mln'] }}</td>
            <td class="r debt-soft">{{ $c['unoverdue_debt_mln'] }}</td>
            <td class="r">{{ $c['diff_mln'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>

<script>
function downloadXlsx() {
    const table = document.getElementById('sheet');
    // Build array of arrays from table, skipping anchor tags (use text only)
    const rows = [];
    for (const tr of table.rows) {
        const row = [];
        for (const cell of tr.cells) {
            // numeric-looking cells: strip spaces and store as number
            const raw = cell.innerText.trim().replace(/\s/g, '');
            const num = raw !== '' && raw !== '—' && !isNaN(raw.replace(',', '.')) ? Number(raw.replace(',', '.')) : null;
            row.push(num !== null ? num : cell.innerText.trim());
        }
        rows.push(row);
    }
    const ws = XLSX.utils.aoa_to_sheet(rows);
    // Set column widths
    ws['!cols'] = [
        {wch:5},{wch:40},{wch:14},{wch:18},{wch:12},
        {wch:14},{wch:18},{wch:18},{wch:18},{wch:20},{wch:22},{wch:18}
    ];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Қарздорлик');
    XLSX.writeFile(wb, 'debts_{{ now()->format("Y_m_d") }}.xlsx');
}

function copyTable() {
    const table = document.getElementById('sheet');
    let text = '';
    for (const tr of table.rows) {
        const cells = [];
        for (const td of tr.cells) {
            cells.push(td.innerText.trim());
        }
        text += cells.join('\t') + '\n';
    }
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('[onclick="copyTable()"]');
        const orig = btn.textContent;
        btn.textContent = '✓ Нусха кўчирилди!';
        setTimeout(() => btn.textContent = orig, 2000);
    }).catch(() => {
        // fallback: select table
        const range = document.createRange();
        range.selectNode(table);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();
    });
}
</script>
</body>
</html>
