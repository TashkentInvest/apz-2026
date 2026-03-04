@extends('layouts.app')

@section('title', 'АПЗ Тўловлар')

@push('styles')
<style>
    /* Table block styling matching reference UI */
    .table-block {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e8e8e8;
        overflow: hidden;
    }

    .table-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e8e8e8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .search-place {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1rem;
        font-weight: 600;
        color: #15191e;
    }

    .search-place input {
        border: 1px solid #dcddde;
        border-radius: 8px;
        padding: 8px 14px;
        font-size: 0.875rem;
        min-width: 200px;
        outline: none;
    }

    .search-place input:focus {
        border-color: #018c87;
    }

    .filters {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        background: #018c87;
        color: #fff;
        transition: all 0.15s;
    }

    .filter-btn:hover {
        background: #017570;
    }

    .filter-btn svg {
        width: 16px;
        height: 16px;
    }

    /* Main table styling */
    .main-table {
        width: 100%;
        overflow-x: auto;
    }

    .main-table table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .main-table thead th {
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        color: #27314b;
        white-space: nowrap;
        background: #fff;
        border-bottom: 1px solid #e8e8e8;
    }

    .main-table thead th .th-inner {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .main-table thead th .th-inner:hover {
        color: #018c87;
    }

    .main-table thead th svg {
        width: 16px;
        height: 16px;
        opacity: 0.5;
    }

    .main-table tbody tr {
        border-bottom: 1px solid #f0f2f5;
        transition: background 0.1s;
    }

    .main-table tbody tr:hover {
        background: #f7f9fa;
    }

    .main-table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        color: #333;
    }

    /* Status badges */
    .status {
        display: inline-flex;
        align-items: center;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 500;
        padding: 5px 11px;
        border: 1px solid;
        white-space: nowrap;
    }

    .status.outline.success {
        background: rgba(6,184,56,.1);
        border-color: #0bc33f;
        color: #0bc33f;
    }

    .status.outline.danger {
        background: rgba(230,50,96,.1);
        border-color: #e63260;
        color: #e63260;
    }

    .status.outline.warning {
        background: rgba(254,197,36,.15);
        border-color: #fec524;
        color: #9a6800;
    }

    /* Action button */
    .action-btn {
        background: none;
        border: none;
        color: #6e788b;
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
    }

    .action-btn:hover {
        background: #f0f2f5;
        color: #15191e;
    }

    .action-btn svg {
        width: 20px;
        height: 20px;
    }

    /* Pagination */
    .pagination-wrap {
        padding: 16px 20px;
        border-top: 1px solid #e8e8e8;
        display: flex;
        justify-content: center;
    }

    .pagination {
        display: flex;
        gap: 4px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .pagination li a,
    .pagination li span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 12px;
        border-radius: 8px;
        font-size: 0.875rem;
        text-decoration: none;
        color: #555;
        background: #fff;
        border: 1px solid #e0e0e0;
        transition: all 0.15s;
    }

    .pagination li a:hover {
        background: #f0f2f5;
        color: #15191e;
    }

    .pagination li.active span {
        background: #018c87;
        color: #fff;
        border-color: #018c87;
    }

    .pagination li.disabled span {
        color: #aab0bb;
        cursor: not-allowed;
    }

    /* Summary stats row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e8e8e8;
    }

    .stat-card .label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6e788b;
        margin-bottom: 8px;
    }

    .stat-card .value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #15191e;
    }

    .stat-card.primary .value { color: #018c87; }
    .stat-card.info .value { color: #1471f0; }
    .stat-card.success .value { color: #0bc33f; }
</style>
@endpush

@section('content')
<!-- APZ Summary Stats -->
<div class="stats-row">
    <div class="stat-card primary">
        <div class="label">Жами Приход (сўм)</div>
        <div class="value">{{ number_format($summary['total_income'], 0, ',', ' ') }}</div>
    </div>
    <div class="stat-card info">
        <div class="label">Жами Чиқим (сўм)</div>
        <div class="value">{{ number_format($summary['total_expense'], 0, ',', ' ') }}</div>
    </div>
    <div class="stat-card success">
        <div class="label">Жами тўлов йозувлари</div>
        <div class="value">{{ number_format($summary['total_records'], 0, ',', ' ') }}</div>
    </div>
</div>

<!-- APZ Payments Form -->
<form method="GET" action="{{ route('home') }}" id="filterForm">
    <div class="table-block mb-3">
        <div class="table-header">
            <div class="search-place">
                АПЗ Тўловлар
                <input type="text" name="search" placeholder="Қидириш..." value="{{ request('search') }}" onkeypress="if(event.key==='Enter') document.getElementById('filterForm').submit()">
            </div>
            <div class="filters">
                <select name="district" class="filter-btn" style="background:#fff;color:#333;border:1px solid #dcddde;" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Барча туманлар</option>
                    @foreach($districts as $district)
                        <option value="{{ $district }}" {{ request('district') == $district ? 'selected' : '' }}>{{ $district }}</option>
                    @endforeach
                </select>
                <select name="year" class="filter-btn" style="background:#fff;color:#333;border:1px solid #dcddde;" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Барча йиллар</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
                <select name="month" class="filter-btn" style="background:#fff;color:#333;border:1px solid #dcddde;" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Барча ойлар</option>
                    @foreach($months as $month)
                        <option value="{{ $month }}" {{ request('month') == $month ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>
                <select name="type" class="filter-btn" style="background:#fff;color:#333;border:1px solid #dcddde;" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Барча тур</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
                <button type="submit" class="filter-btn">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M6.768 4.066A2.5 2.5 0 113.232 7.6a2.5 2.5 0 013.536-3.535M16.667 5.833H7.5M16.768 12.399a2.5 2.5 0 11-3.536 3.535 2.5 2.5 0 013.536-3.535M3.333 14.167H12.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Филтр
                </button>
                <a href="{{ route('home') }}" class="filter-btn" style="background:#6e788b;text-decoration:none;">Тозалаш</a>
            </div>
        </div>
    </div>
</form>

<!-- APZ Payments Table -->
<div class="table-block">
    <div class="main-table">
        <table>
            <thead>
                <tr>
                    @php
                    $sortField = request('sort', 'id');
                    $sortDir   = request('dir', 'desc');
                    $nextDir   = $sortDir === 'asc' ? 'desc' : 'asc';
                    $qBase     = request()->except(['sort','dir','page']);
                    $sortUrl   = function($field) use ($qBase, $sortField, $nextDir) {
                        $dir = ($sortField === $field) ? $nextDir : 'desc';
                        return url()->current() . '?' . http_build_query(array_merge($qBase, ['sort'=>$field,'dir'=>$dir]));
                    };
                    @endphp
                    @php
                    $cols = [
                        'id'           => 'ID',
                        'payment_date' => 'Сана',
                        'district'     => 'Туман',
                        'type'         => 'Тури',
                    ];
                    @endphp
                    @foreach($cols as $col => $label)
                    <th>
                        <a href="{{ $sortUrl($col) }}" class="th-inner" style="text-decoration:none;color:inherit;">
                            {{ $label }}
                            <svg viewBox="0 0 16 16" fill="none" style="{{ $sortField === $col ? 'opacity:1;color:#018c87;' : '' }}">
                                @if($sortField === $col && $sortDir === 'asc')
                                <path d="M8 2L4.667 6.667h6.666L8 2zM8 14l3.333-4.667H4.667L8 14z" fill="#018c87"/>
                                @elseif($sortField === $col)
                                <path d="M8 14l3.333-4.667H4.667L8 14zM8 2L4.667 6.667h6.666L8 2z" fill="#018c87"/>
                                @else
                                <path d="M8 14a.605.605 0 01-.467-.2L4.2 10.466a.644.644 0 010-.933.644.644 0 01.933 0L8 12.4l2.867-2.867a.644.644 0 01.933 0 .644.644 0 010 .933L8.467 13.8A.605.605 0 018 14zM4.667 6.667a.605.605 0 01-.467-.2.644.644 0 010-.934L7.533 2.2a.644.644 0 01.934 0L11.8 5.533a.644.644 0 010 .934.644.644 0 01-.933 0L8 3.6 5.133 6.467a.605.605 0 01-.466.2z" fill="#78829D"/>
                                @endif
                            </svg>
                        </a>
                    </th>
                    @endforeach
                    <th>Инвестор</th>
                    <th>Ой/Йил</th>
                    <th>Поток</th>
                    <th>
                        <a href="{{ $sortUrl('amount') }}" class="th-inner" style="text-decoration:none;color:inherit;">
                            Сумма
                            <svg viewBox="0 0 16 16" fill="none"><path d="M8 14a.605.605 0 01-.467-.2L4.2 10.466a.644.644 0 010-.933.644.644 0 01.933 0L8 12.4l2.867-2.867a.644.644 0 01.933 0 .644.644 0 010 .933L8.467 13.8A.605.605 0 018 14zM4.667 6.667a.605.605 0 01-.467-.2.644.644 0 010-.934L7.533 2.2a.644.644 0 01.934 0L11.8 5.533a.644.644 0 010 .934.644.644 0 01-.933 0L8 3.6 5.133 6.467a.605.605 0 01-.466.2z" fill="#78829D"/></svg>
                        </a>
                    </th>
                    <th>Тўлов мақсади</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                @php
                    $drawerData = [
                        'id'           => $transaction->id,
                        'district'     => $transaction->district,
                        'date'         => $transaction->payment_date ? date('d.m.Y', strtotime($transaction->payment_date)) : '',
                        'type'         => $transaction->type,
                        'month_year'   => ($transaction->month ?? '') . '/' . ($transaction->year ?? ''),
                        'flow'         => $transaction->flow,
                        'amount'       => number_format($transaction->amount, 0, ',', ' '),
                        'purpose'      => $transaction->payment_purpose,
                        'company'      => $transaction->company_name ?? $transaction->investor_name ?? '',
                        'contract'     => $transaction->contract_number ?? '',
                        'contract_id'  => $transaction->contract_id,
                        'inn'          => $transaction->inn,
                    ];
                @endphp
                    <tr onclick="openDrawer({{ json_encode($drawerData) }})" style="cursor:pointer;">
                        <td>#{{ $transaction->id }}</td>
                        <td>{{ $transaction->payment_date ? date('d.m.Y', strtotime($transaction->payment_date)) : '—' }}</td>
                        <td>{{ $transaction->district }}</td>
                        <td><span class="status outline {{ $transaction->type === 'АПЗ тўлови' ? 'info' : ($transaction->type === 'Пеня тўлови' ? 'warning' : 'danger') }}">{{ $transaction->type ?: '—' }}</span></td>
                        <td style="font-size:.8rem;color:#018c87;">{{ Str::limit($transaction->company_name ?? $transaction->investor_name ?? '', 28) }}</td>
                        <td>{{ $transaction->month }} / {{ $transaction->year }}</td>
                        <td>
                            <span class="status outline {{ $transaction->flow === 'Приход' ? 'success' : 'danger' }}">
                                {{ $transaction->flow }}
                            </span>
                        </td>
                        <td style="font-weight:600;">{{ number_format($transaction->amount, 0, ',', ' ') }} сўм</td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $transaction->payment_purpose }}">
                            {{ Str::limit($transaction->payment_purpose, 35) }}
                        </td>
                        <td onclick="event.stopPropagation()">
                            <button type="button" class="action-btn" title="Кўриш"
                                onclick="openDrawer({{ json_encode($drawerData) }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="2.997" y="2.997" width="18.008" height="18.008" rx="5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12.5 8.499h3.002v3M11.5 15.502H8.499V12.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:40px;color:#6e788b;">
                            Маълумот топилмади
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $transactions->links() }}
    </div>
</div>

@push('scripts')
<script>
function openDrawer(d) {
    const items = [
        ['ИД', '#' + d.id],
        ['Сана', d.date],
        ['Шартнома #', d.contract_id || '—'],
        ['Шартнома рақами', d.contract || '—'],
        ['Инвестор', d.company || '—'],
        ['ИНН / ПИНФЛ', d.inn || '—'],
        ['Туман', d.district],
        ['Тури', d.type],
        ['Ой / Йил', d.month_year],
        ['Поток', d.flow, d.flow && d.flow.includes('Приход') ? '#0bc33f' : '#e63260'],
        ['Сумма', d.amount + " сўм"],
        ["Тўлов мақсади", d.purpose],
    ];
    const ul = document.getElementById('drawer-content');
    ul.innerHTML = items.map(([label, val, color]) => `
        <li style="padding:14px 0;border-bottom:1px solid #f0f2f5;display:flex;flex-direction:column;gap:4px;">
            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#aab0bb;">${label}</span>
            <span style="font-size:.9rem;color:${color||'#15191e'};font-weight:${color?'700':'500'};">${val || '—'}</span>
        </li>
    `).join('');
    document.getElementById('drawer-overlay').style.display = 'block';
    document.getElementById('detail-drawer').style.right = '0';
}
function closeDrawer() {
    document.getElementById('drawer-overlay').style.display = 'none';
    document.getElementById('detail-drawer').style.right = '-440px';
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeDrawer(); });
</script>
@endpush
@endsection
