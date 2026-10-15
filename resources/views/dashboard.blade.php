@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard-redesigned.css') }}?v={{ config('app.ASSET_VERSION') }}">
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
        @if (isset($dashboardView) && in_array($dashboardView, ['master_admin', 'super_admin', 'admin', 'moderator', 'accountant'], true))
            @include('dashboard.roles.'.$dashboardView)
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
                document.querySelectorAll('[data-dashboard-analytics]').forEach((root) => {
                    root.querySelectorAll('[data-dash-tab]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const tab = btn.getAttribute('data-dash-tab');
                            root.querySelectorAll('[data-dash-tab]').forEach((b) => b.classList.toggle('is-active', b === btn));
                            root.querySelectorAll('[data-dash-pane]').forEach((pane) => {
                                pane.hidden = pane.getAttribute('data-dash-pane') !== tab;
                            });
                        });
                    });
                });
            }

            const compactRupees = (value) => '₹' + Number(value).toLocaleString('en-IN', { notation: 'compact', maximumFractionDigits: 1 });
            const fullRupees = (value) => '₹' + Number(value).toLocaleString('en-IN', { maximumFractionDigits: 0 });

            const legendTop = {
                display: true,
                position: 'top',
                labels: { usePointStyle: true, boxWidth: 8, font: { size: 11, weight: '600' } },
            };

            const moneyTooltip = {
                callbacks: {
                    label: (context) => context.dataset.label + ': ' + fullRupees(context.raw),
                },
            };

            const categoryAxis = { grid: { display: false }, ticks: { font: { size: 10, weight: '600' } } };
            const moneyAxis = { beginAtZero: true, grid: { color: brand.grid }, ticks: { callback: compactRupees } };

            function renderMonthlyPnl(canvas, trend) {
                new Chart(canvas, {
                    data: {
                        labels: trend.labels || [],
                        datasets: [
                            { type: 'bar', label: 'Revenue', data: trend.revenue || [], backgroundColor: 'rgba(47, 179, 111, 0.8)', borderRadius: 6, maxBarThickness: 26, order: 2 },
                            { type: 'bar', label: 'Trip cost', data: trend.cost || [], backgroundColor: 'rgba(217, 119, 6, 0.75)', borderRadius: 6, maxBarThickness: 26, order: 2 },
                            { type: 'line', label: 'Profit', data: trend.profit || [], borderColor: brand.blue, backgroundColor: brand.blue, tension: 0.35, pointRadius: 3, order: 1 },
                            { type: 'line', label: 'Collected', data: trend.collected || [], borderColor: brand.slate, borderDash: [5, 4], backgroundColor: brand.slate, tension: 0.35, pointRadius: 2, order: 1 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: legendTop, tooltip: moneyTooltip },
                        scales: { x: categoryAxis, y: moneyAxis },
                    },
                });
            }

            function renderProfitTrend(canvas, trend) {
                new Chart(canvas, {
                    data: {
                        labels: trend.labels || [],
                        datasets: [
                            { type: 'bar', label: 'Revenue', data: trend.revenue || [], backgroundColor: 'rgba(47, 179, 111, 0.75)', borderRadius: 4, maxBarThickness: 18, order: 2 },
                            { type: 'bar', label: 'Trip cost', data: trend.cost || [], backgroundColor: 'rgba(217, 119, 6, 0.7)', borderRadius: 4, maxBarThickness: 18, order: 2 },
                            {
                                type: 'line',
                                label: 'Profit',
                                data: trend.profit || [],
                                borderColor: brand.blue,
                                backgroundColor: brand.blue,
                                tension: 0.3,
                                pointRadius: 2,
                                order: 1,
                                segment: { borderColor: (ctx) => (ctx.p1.parsed.y < 0 ? brand.red : brand.blue) },
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: legendTop, tooltip: moneyTooltip },
                        scales: { x: categoryAxis, y: { ...moneyAxis, beginAtZero: false } },
                    },
                });
            }

            function renderPlatformTrend(canvas, trend) {
                new Chart(canvas, {
                    data: {
                        labels: trend.labels || [],
                        datasets: [
                            { type: 'bar', label: 'Subscription revenue', data: trend.subscription_revenue || [], backgroundColor: 'rgba(47, 179, 111, 0.8)', borderRadius: 6, maxBarThickness: 28, yAxisID: 'y', order: 2 },
                            { type: 'line', label: 'Bilties created', data: trend.lrs || [], borderColor: brand.blue, backgroundColor: brand.blue, tension: 0.35, pointRadius: 3, yAxisID: 'y1', order: 1 },
                            { type: 'line', label: 'New companies', data: trend.signups || [], borderColor: brand.amber, backgroundColor: brand.amber, borderDash: [5, 4], tension: 0.35, pointRadius: 3, yAxisID: 'y1', order: 1 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: legendTop,
                            tooltip: {
                                callbacks: {
                                    label: (context) => context.dataset.yAxisID === 'y'
                                        ? context.dataset.label + ': ' + fullRupees(context.raw)
                                        : context.dataset.label + ': ' + Number(context.raw).toLocaleString('en-IN'),
                                },
                            },
                        },
                        scales: {
                            x: categoryAxis,
                            y: moneyAxis,
                            y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0 } },
                        },
                    },
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

                const charts = [
                    ['dashMonthlyPnlChart', renderMonthlyPnl, payload.monthlyTrend],
                    ['dashProfitTrendChart', renderProfitTrend, payload.profitTrend],
                    ['dashPlatformTrendChart', renderPlatformTrend, payload.platformTrend],
                ];

                charts.forEach(([id, render, data]) => {
                    const canvas = document.getElementById(id);
                    if (canvas && data) {
                        render(canvas, data);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                animateCountUps();
                initTabs();
                initCharts();
            });
        })();
    </script>
@endpush
