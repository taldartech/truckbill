@extends('layouts.app')

@section('content')
@include('subscription.partials.styles')

@php
    $status = $subscription?->displayStatus() ?? 'none';
    $catalogData = $catalog['catalog'] ?? [];
    $isRenewal = $purchaseType === 'renewal';
    $minBranches = (int) ($minimumAddons['addon_branches'] ?? 0);
    $minUsers = (int) ($minimumAddons['addon_users'] ?? 0);
    $branchLabelSuffix = $isRenewal && $minBranches > 0 ? " (min {$minBranches})" : '';
    $userLabelSuffix = $isRenewal && $minUsers > 0 ? " (min {$minUsers})" : '';
    $branchInputMin = $isRenewal ? $minBranches : 0;
    $userInputMin = $isRenewal ? $minUsers : 0;
    $remainingDays = ! $isRenewal ? ($subscription?->remainingDaysInclusive() ?? 0) : null;
@endphp

<div class="sub-page" id="subscription-checkout" data-company-id="{{ $company->id }}">
    <a href="{{ route('subscription.show', ['company_id' => $company->id]) }}" class="sub-back">
        <i class="fas fa-arrow-left"></i> Back to plan details
    </a>

    <div class="sub-hero">
        <div>
            <h1 class="sub-hero-title">{{ $isRenewal ? 'Renew plan' : 'Buy add-ons' }}</h1>
            <p class="sub-hero-subtitle">{{ $company->name }} — configure your order and pay securely.</p>
        </div>
        <span class="sub-badge sub-badge-{{ $status }}">
            <i class="fas fa-circle" style="font-size: 0.45rem;"></i>
            {{ str_replace('_', ' ', $status) }}
        </span>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="sub-card">
                <div class="sub-card-header">
                    <div class="sub-card-label">Reference</div>
                    <h2 class="sub-card-title mb-0">Catalog pricing</h2>
                </div>
                <div class="sub-card-body">
                    <div class="sub-quote-lines">
                        <div class="sub-quote-line">
                            <span>Annual base plan</span>
                            <span>₹{{ number_format((float) ($catalogData['base_price'] ?? 0), 2) }}</span>
                        </div>
                        <div class="sub-quote-line">
                            <span>Branches included</span>
                            <span>{{ $catalogData['included_branches'] ?? '—' }}</span>
                        </div>
                        <div class="sub-quote-line">
                            <span>Users per branch</span>
                            <span>{{ $catalogData['users_per_branch'] ?? '—' }}</span>
                        </div>
                        <div class="sub-quote-line">
                            <span>Branch add-on</span>
                            <span>₹{{ number_format((float) ($catalogData['addon_branch_price'] ?? 0), 2) }}/yr</span>
                        </div>
                        <div class="sub-quote-line">
                            <span>User add-on</span>
                            <span>₹{{ number_format((float) ($catalogData['addon_user_price'] ?? 0), 2) }}/yr</span>
                        </div>
                        @if (! $isRenewal && $remainingDays)
                            <p class="sub-success-note mt-3 mb-0">
                                Add-ons are charged only for the remaining {{ $remainingDays }} day(s) of your current plan
                                (annual price × days ÷ 365).
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="sub-card">
                <div class="sub-card-header">
                    <div class="sub-card-label">Checkout</div>
                    <h2 class="sub-card-title mb-0">{{ $isRenewal ? 'Renew your subscription' : 'Add capacity to your plan' }}</h2>
                </div>
                <div class="sub-card-body">
                    <input type="hidden" id="purchase_type" value="{{ $purchaseType }}">

                    @if ($isRenewal && $usage)
                        <div class="sub-alert sub-alert-warning mb-3">
                            Current usage: {{ $usage['branches'] }} branch(es), {{ $usage['total_users'] }} user(s).
                            Renewal must cover this usage — add the required add-ons below or remove branches/users first.
                        </div>
                    @elseif (! $isRenewal && $remainingDays)
                        <div class="sub-alert sub-alert-info mb-3">
                            Your plan ends on {{ $subscription->ends_on->format('d M Y') }}.
                            Add-on charges are prorated for {{ $remainingDays }} remaining day(s).
                        </div>
                    @endif

                    <div class="sub-addon-grid">
                        <div class="sub-field mb-0">
                            <label for="addon_branches">Extra branches{{ $branchLabelSuffix }}</label>
                            <input type="number" min="{{ $branchInputMin }}" class="sub-input quote-input" id="addon_branches" value="{{ $isRenewal ? $minBranches : 0 }}">
                        </div>
                        <div class="sub-field mb-0">
                            <label for="addon_users">Extra users{{ $userLabelSuffix }}</label>
                            <input type="number" min="{{ $userInputMin }}" class="sub-input quote-input" id="addon_users" value="{{ $isRenewal ? $minUsers : 0 }}">
                        </div>
                    </div>

                    <div class="sub-field">
                        <label for="promo_code">Promo code</label>
                        <div class="sub-promo-row">
                            <input type="text" class="sub-input" id="promo_code" placeholder="Enter code">
                            <button type="button" class="sub-btn-secondary" id="validate_promo">Apply</button>
                        </div>
                    </div>

                    <div id="quote_feedback" class="sub-error"></div>
                    <div id="promo_feedback" class="sub-success-note"></div>

                    <div class="sub-quote-box">
                        <div class="sub-card-label mb-2">Order summary</div>
                        <div id="quote_lines" class="sub-quote-lines">
                            <div class="sub-quote-line">
                                <span>Calculating quote…</span>
                                <span>—</span>
                            </div>
                        </div>
                        <div class="sub-quote-total">
                            <span class="sub-quote-total-label">Total payable</span>
                            <span class="sub-quote-total-value">₹<span id="quote_total">0.00</span></span>
                        </div>
                    </div>

                    <button type="button" class="sub-btn-primary" id="pay_btn" disabled>
                        <i class="fas fa-lock"></i> Pay securely with Razorpay
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(() => {
    const root = document.getElementById('subscription-checkout');
    const companyId = root.dataset.companyId;
    const purchaseTypeInput = document.getElementById('purchase_type');
    let promoValidated = false;
    let quoteTimer = null;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const quoteErrorsEl = document.getElementById('quote_feedback');
    const promoFeedbackEl = document.getElementById('promo_feedback');
    const quoteLinesEl = document.getElementById('quote_lines');
    const quoteTotalEl = document.getElementById('quote_total');
    const payBtn = document.getElementById('pay_btn');
    const manageUrl = @json(route('subscription.show', ['company_id' => $company->id]));

    function payload() {
        return {
            company_id: companyId,
            purchase_type: purchaseTypeInput.value,
            addon_branches: Number(document.getElementById('addon_branches').value || 0),
            addon_users: Number(document.getElementById('addon_users').value || 0),
            addon_trucks: 0,
            addon_lr_credits: 0,
            promo_code: document.getElementById('promo_code').value || null,
            promo_validated: promoValidated,
        };
    }

    function renderQuoteLines(items) {
        if (!items.length) {
            quoteLinesEl.innerHTML = '<div class="sub-quote-line"><span>No line items yet</span><span>₹0.00</span></div>';
            return;
        }

        quoteLinesEl.innerHTML = items.map((item) => {
            const amount = Number(item.amount || 0);
            const isDiscount = amount < 0;

            return `<div class="sub-quote-line${isDiscount ? ' discount' : ''}">
                <span>${item.label}</span>
                <span>${isDiscount ? '−' : ''}₹${Math.abs(amount).toFixed(2)}</span>
            </div>`;
        }).join('');
    }

    async function fetchQuote() {
        payBtn.disabled = true;

        try {
            const res = await fetch('{{ route('subscription.quote') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload()),
            });
            const quote = await res.json();

            if (!res.ok) {
                quoteErrorsEl.textContent = quote.message || 'Unable to load pricing. Please try again later.';
                renderQuoteLines([]);
                quoteTotalEl.textContent = '0.00';
                payBtn.disabled = true;
                return;
            }

            quoteErrorsEl.textContent = (quote.blocking_errors || []).join(' ');
            renderQuoteLines(quote.line_items || []);
            quoteTotalEl.textContent = Number(quote.total_amount || 0).toFixed(2);
            payBtn.disabled = !quote.can_checkout;
        } catch (error) {
            quoteErrorsEl.textContent = 'Unable to load pricing. Please try again later.';
            payBtn.disabled = true;
        }
    }

    function scheduleQuote() {
        clearTimeout(quoteTimer);
        quoteTimer = setTimeout(fetchQuote, 300);
    }

    document.querySelectorAll('.quote-input').forEach((el) => {
        el.addEventListener('input', () => {
            promoValidated = false;
            promoFeedbackEl.textContent = '';
            scheduleQuote();
        });
    });

    document.getElementById('promo_code').addEventListener('input', () => {
        promoValidated = false;
        promoFeedbackEl.textContent = '';
    });

    document.getElementById('validate_promo').addEventListener('click', async () => {
        const code = document.getElementById('promo_code').value.trim();
        promoFeedbackEl.textContent = '';
        quoteErrorsEl.textContent = '';

        if (!code) {
            quoteErrorsEl.textContent = 'Enter a promo code first.';
            return;
        }

        const res = await fetch('{{ route('subscription.promo.validate') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ code }),
        });
        const data = await res.json();
        promoValidated = !!data.valid;

        if (data.valid) {
            promoFeedbackEl.textContent = 'Promo code applied successfully.';
        } else {
            quoteErrorsEl.textContent = data.message || 'Invalid promo code.';
        }

        fetchQuote();
    });

    document.getElementById('pay_btn').addEventListener('click', async () => {
        payBtn.disabled = true;

        try {
            const res = await fetch('{{ route('subscription.checkout') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload()),
            });
            const order = await res.json();

            if (!order.razorpay_order_id) {
                quoteErrorsEl.textContent = 'Could not start checkout. Please try again.';
                payBtn.disabled = false;
                return;
            }

            const rzp = new Razorpay({
                key: order.razorpay_key,
                amount: order.razorpay_amount_paise,
                currency: 'INR',
                order_id: order.razorpay_order_id,
                name: '{{ $company->name }}',
                description: 'TruckBill subscription',
                handler: async (response) => {
                    await fetch('{{ route('subscription.callback') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify(response),
                    });
                    window.location.href = manageUrl;
                },
                modal: {
                    ondismiss: () => {
                        payBtn.disabled = false;
                    },
                },
            });
            rzp.open();
        } catch (error) {
            quoteErrorsEl.textContent = 'Checkout failed. Please try again.';
            payBtn.disabled = false;
        }
    });

    fetchQuote();
})();
</script>
@endsection
