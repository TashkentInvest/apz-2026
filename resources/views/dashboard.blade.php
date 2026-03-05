@extends('layouts.app')

@section('title', 'АПЗ Дашбоард')
@section('content')
@php
    use App\Models\ApzPayment;
    use App\Models\ApzContract;
    $user = auth()->user();

    $totalPayments  = ApzPayment::count();
    $totalIncome    = ApzPayment::where('flow', 'Приход')->sum('amount');
    $totalContracts = ApzContract::count();
    $uniqueDistricts = ApzPayment::distinct()->count('district');
@endphp

{{-- Welcome --}}
<div style="margin-bottom:20px">
    <div style="font-size:1.1rem;font-weight:700;color:#15191e">
        Xush kelibsiz, {{ $user->name }}
    </div>
    <div style="font-size:0.85rem;color:#6e788b;margin-top:3px">
        {{ $user->role }}
    </div>
</div>

{{-- Stat cards --}}
<div class="stat-cards-row">
    <div class="stat-card-p sc-teal">
        <div class="sc-label">Жами тўловлар</div>
        <div class="sc-value">{{ number_format($totalPayments) }}</div>
    </div>
    <div class="stat-card-p sc-green-dk">
        <div class="sc-label">Жами Приход</div>
        <div class="sc-value">{{ number_format($totalIncome, 0, '.', ' ') }}</div>
    </div>
    <div class="stat-card-p sc-orange">
        <div class="sc-label">Шартномалар</div>
        <div class="sc-value">{{ number_format($totalContracts) }}</div>
    </div>
    <div class="stat-card-p sc-blue">
        <div class="sc-label">Туманлар</div>
        <div class="sc-value">{{ $uniqueDistricts }}</div>
    </div>
</div>

{{-- Quick links --}}
<div class="row g-3">
    <div class="col-lg-6">
        <div class="block">
            <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aab0bb;margin-bottom:16px">Tez o'tish</div>

            <a href="{{ route('home') }}" class="quick-link">
                <div class="quick-link-icon ql-teal">💳</div>
                <div class="quick-link-info">
                    <strong>АПЗ Тўловлар</strong>
                    <span>Барча тўловларни кўриш</span>
                </div>
            </a>

            <a href="{{ route('dashboard') }}" class="quick-link">
                <div class="quick-link-icon ql-blue">📊</div>
                <div class="quick-link-info">
                    <strong>АПЗ Дашбоард</strong>
                    <span>План-факт статистикаси</span>
                </div>
            </a>

            <a href="{{ route('summary2') }}" class="quick-link">
                <div class="quick-link-icon ql-green">📄</div>
                <div class="quick-link-info">
                    <strong>Шартномалар (План—Факт)</strong>
                    <span>Договор бўйича жадвал</span>
                </div>
            </a>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="block h-100">
            <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aab0bb;margin-bottom:16px">Ma'lumot</div>
            <div style="font-size:.85rem;color:#5a6a8a;line-height:1.6;margin-bottom:14px">
                <strong style="color:#15191e">АПЗ мониторинг тизими</strong> — Тошкент шаҳар туманлари бўйича
                архитектура—режалаштириш топшириғи (АПЗ) тўловларини
                кузатиш ва таҳлил қилиш.
            </div>
            <div style="font-size:.82rem;color:#6e788b;line-height:1.8">
                <div>&bull; fakt_apz.csv — ҳақиқий APZ тўловлари</div>
                <div>&bull; grafik_apz.csv — шартнома ва режа-жадвал</div>
                <div>&bull; ID буйича жойн уланган</div>
                <div>&bull; План — Факт ҳисоботи</div>
            </div>
        </div>
    </div>
</div>

@endsection
