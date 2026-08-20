@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard-redesigned.css') }}">
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success mt-2" style="max-width: 1280px; margin-left: auto; margin-right: auto;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-2" style="max-width: 1280px; margin-left: auto; margin-right: auto;">
            {{ session('error') }}
        </div>
    @endif

    <div class="dashboard-page">
        @php
            $userRole = auth()->user()->userRole->name;
        @endphp

        @if ($userRole === 'master_admin' && isset($systemStats))
            @include('dashboard.roles.master_admin')
        @elseif ($userRole === 'super_admin' && isset($adminStats))
            @include('dashboard.roles.super_admin')
        @elseif ((($userRole === 'admin') || ($userRole === 'super_admin' && session('super_admin_company_id'))) && isset($monthlyStats))
            @include('dashboard.roles.admin')
        @elseif ($userRole === 'moderator' && isset($operationalStats))
            @include('dashboard.roles.moderator')
        @elseif ($userRole === 'accountant' && isset($financialStats))
            @include('dashboard.roles.accountant')
        @elseif ($userRole === 'transporter' && isset($transporterStats))
            @include('dashboard.roles.transporter')
        @elseif ($userRole === 'customer' && isset($customerStats))
            @include('dashboard.roles.customer')
        @else
            <div class="dashboard-panel">
                <div class="dashboard-panel__body">
                    <div class="dashboard-empty">
                        Welcome to Dashboard. Content for your role is being prepared.
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const brand = {
                green: '#2fb36f',
                greenDark: '#23965c',
                blue: '#2d8cff',
                amber: '#d97706',
                red: '#ef4444',
                slate: '#64748b',
                grid: '#e2e8f0',
            };

            function animateCountUps() {
                document.querySelectorAll('[data-count-up]').forEach((el) => {
                    const raw = el.getAttribute('data-count-up');
                    const target = Number(String(raw).replace(/[^0-9.-]/g, ''));
                    if (!Number.isFinite(target)) {
                        return;
                    }
                    const isMoney = String(el.textContent || '').includes('₹') || String(raw).includes('.');
                    const duration = 700;
                    const start = performance.now();
                    const from = 0;

                    function frame(now) {
                        const t = Math.min(1, (now - start) / duration);
                        const eased = 1 - Math.pow(1 - t, 3);
                        const current = from + (target - from) * eased;
                        if (isMoney) {
                            el.textContent = '₹' + current.toLocaleString('en-IN', {
                                minimumFractionDigits: target % 1 ? 2 : 0,
                                maximumFractionDigits: 2,
                            });
                        } else {
                            el.textContent = Math.round(current).toLocaleString('en-IN');
                        }
                        if (t < 1) {
                            requestAnimationFrame(frame);
                        }
                    }

                    requestAnimationFrame(frame);
                });
            }

            function initTabs() {
                const root = document.querySelector('[data-dashboard-analytics]');
                if (!root) {
                    return;
                }

                root.querySelectorAll('[data-dash-tab]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const tab = btn.getAttribute('data-dash-tab');
                        root.querySelectorAll('[data-dash-tab]').forEach((b) => b.classList.toggle('is-active', b === btn));
                        root.querySelectorAll('[data-dash-pane]').forEach((pane) => {
                            pane.hidden = pane.getAttribute('data-dash-pane') !== tab;
                        });
                    });
                });
            }

            function initCharts() {
                const dataEl = document.getElementById('dashboard-chart-data');
                if (!dataEl || typeof Chart === 'undefined') {
                    return;
                }

                let payload = {};
                try {
                    payload = JSON.parse(dataEl.textContent || '{}');
                } catch (e) {
                    return;
                }

                const lrCounts = payload.lrStatusCounts || {};
                const payments = payload.invoicePaymentStatuses || {};
                const trend = payload.monthlyTrend || { labels: [], bookings: [], revenue: [] };

                const doughnutDefaults = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, usePointStyle: true, font: { size: 11, weight: '600' } },
                        },
                    },
                    cutout: '62%',
                };

                const lrCanvas = document.getElementById('dashLrStatusChart');
                if (lrCanvas) {
                    new Chart(lrCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(lrCounts),
                            datasets: [{
                                data: Object.values(lrCounts),
                                backgroundColor: [brand.amber, brand.blue, brand.green, brand.red],
                                borderWidth: 0,
                            }],
                        },
                        options: doughnutDefaults,
                    });
                }

                const paymentCanvas = document.getElementById('dashPaymentStatusChart');
                if (paymentCanvas) {
                    new Chart(paymentCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pending', 'Partial', 'Paid'],
                            datasets: [{
                                data: [payments.pending || 0, payments.partial || 0, payments.paid || 0],
                                backgroundColor: [brand.amber, brand.blue, brand.green],
                                borderWidth: 0,
                            }],
                        },
                        options: doughnutDefaults,
                    });
                }

                const bookingCanvas = document.getElementById('dashBookingTrendChart');
                if (bookingCanvas) {
                    new Chart(bookingCanvas, {
                        type: 'bar',
                        data: {
                            labels: trend.labels || [],
                            datasets: [{
                                label: 'Hires',
                                data: trend.bookings || [],
                                backgroundColor: 'rgba(47, 179, 111, 0.75)',
                                borderRadius: 8,
                                maxBarThickness: 28,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' } } },
                                y: { beginAtZero: true, grid: { color: brand.grid }, ticks: { precision: 0 } },
                            },
                        },
                    });
                }

                const revenueCanvas = document.getElementById('dashRevenueTrendChart');
                if (revenueCanvas) {
                    new Chart(revenueCanvas, {
                        type: 'line',
                        data: {
                            labels: trend.labels || [],
                            datasets: [{
                                label: 'Revenue',
                                data: trend.revenue || [],
                                borderColor: brand.greenDark,
                                backgroundColor: 'rgba(47, 179, 111, 0.12)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointBackgroundColor: brand.green,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' } } },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: brand.grid },
                                    ticks: {
                                        callback: (v) => '₹' + Number(v).toLocaleString('en-IN', { notation: 'compact' }),
                                    },
                                },
                            },
                        },
                    });

                }
                                const profitabilityCanvas = document.getElementById('dashProfitabilityTrendChart');

                if (profitabilityCanvas) {
                    const profitability = payload.profitabilityTrend || {};

                    new Chart(profitabilityCanvas, {
                        type: 'line',
                        data: {
                            labels: profitability.labels || [],
                            datasets: [
                                {
                                    label: 'Revenue',
                                    data: profitability.revenue || [],
                                    borderColor: brand.greenDark,
                                    backgroundColor: 'rgba(47, 179, 111, 0.08)',
                                    fill: false,
                                    tension: 0.35,
                                    pointRadius: 2,
                                },
                                {
                                    label: 'Cost',
                                    data: profitability.cost || [],
                                    borderColor: brand.orange,
                                    backgroundColor: 'transparent',
                                    fill: false,
                                    tension: 0.35,
                                    pointRadius: 2,
                                },
                                {
                                    label: 'Profit',
                                    data: profitability.profit || [],
                                    borderColor: brand.blue,
                                    backgroundColor: 'transparent',
                                    fill: false,
                                    tension: 0.35,
                                    pointRadius: 2,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        font: { size: 10, weight: '600' },
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: brand.grid },
                                    ticks: {
                                        callback: (v) =>
                                            '₹' + Number(v).toLocaleString('en-IN', {
                                                notation: 'compact',
                                            }),
                                    },
                                },
                            },
                        },
                    });
                }
            }




 
  

            document.addEventListener('DOMContentLoaded', function () {
                animateCountUps();
                initTabs();
                initCharts();
            });
        })();
    </script>
@endpush
