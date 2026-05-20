@extends('layouts.app')

@push('styles')
    <style>
        .flow-section {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }

        .flow-section.active {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }
    </style>
@endpush

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-credit-card"></i> Payment Management - {{ $company->name }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('payment.index') }}" class="btn btn-sm btn-back">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- Company Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Company Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Company Name:</th>
                                        <td>{{ $company->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Current Plan End Date:</th>
                                        <td>
                                            @if ($company->plan_end_date)
                                                {{ \Carbon\Carbon::parse($company->plan_end_date)->format('d M, Y') }}
                                                @if ($isExpired)
                                                    <span class="badge bg-danger ms-2">Expired</span>
                                                @else
                                                    <span class="badge bg-success ms-2">Active</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Not Set</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>LR Credits:</th>
                                        <td>{{ $company->lr_credits ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Truck Credits:</th>
                                        <td>{{ $company->truck_credits ?? 0 }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Flowchart Logic -->
                        <form id="paymentForm" method="POST" onsubmit="return processPayment(event)">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                            @if (!$isExpired)
                                <div class="flow-section active" id="renewalDecisionSection">
                                    <h4 class="mb-3">
                                        <i class="fas fa-question-circle text-info"></i> Plan Status
                                    </h4>
                                    <p class="mb-3">Your plan is currently active. What would you like to do?</p>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="want_to_renew"
                                                    id="want_to_renew_yes" value="yes"
                                                    {{ old('want_to_renew') == 'yes' ? 'checked' : '' }}
                                                    onchange="toggleRenewalOptions()">
                                                <label class="form-check-label" for="want_to_renew_yes">
                                                    <strong>Yes, I want to renew the plan</strong>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="want_to_renew"
                                                    id="want_to_renew_no" value="no"
                                                    {{ old('want_to_renew') == 'no' ? 'checked' : '' }}
                                                    onchange="toggleRenewalOptions()">
                                                <label class="form-check-label" for="want_to_renew_no">
                                                    <strong>No, I want to add features to current plan</strong>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <!-- Renewal Options (shown when want_to_renew = yes) -->
                            <div class="flow-section form-section @if ($isExpired) active @endif"
                                id="renewalSection">
                                <h4 class="mb-3">
                                    <i class="fas fa-sync-alt text-primary"></i> Renewal Options
                                </h4>

                                <div class="row">
                                    @if ($company->company_type != 'Broker')
                                        <!-- Truck Count -->
                                        <div class="col-md-6 mb-3">
                                            <label for="truck_count" class="form-label">
                                                <strong>Truck</strong> <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control" id="truck_count" name="truck_count"
                                                value="{{ old('truck_count', $company->truck_credits ?? 1) }}"
                                                min="1" onchange="paymentDetailsUpdate()">
                                            <small class="text-muted">Enter number of trucks</small>
                                        </div>

                                        <!-- Truck Rate Details -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-label">
                                                <strong>Truck Rate Details (Annually)</strong>
                                            </div>
                                            <ul>
                                                @foreach ($truck_rate as $count => $rate)
                                                    <li>
                                                        {{ $count }} Trucks (Rs.
                                                        {{ number_format($rate, 2) }})
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if ($company->company_type != 'Agency')
                                        <!-- LR Credits -->
                                        <div class="col-md-12 mb-3">
                                            <label for="lr_credits" class="form-label">
                                                <strong>LR Credits</strong> <span class="text-danger">*</span>
                                            </label>
                                            <div class="form-group">
                                                @foreach ($lr_credits as $credits => $rate)
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="lr_credits"
                                                            id="lr_credits_{{ $credits }}"
                                                            {{ old('lr_credits') == $credits ? 'checked' : '' }}
                                                            value="{{ $credits }}" onchange="paymentDetailsUpdate()">
                                                        <label class="form-check-label"
                                                            for="lr_credits_{{ $credits }}">
                                                            {{ $credits }} Credits (Rs.
                                                            {{ number_format($rate, 2) }})
                                                        </label>
                                                    </div>
                                                @endforeach
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="lr_credits"
                                                        id="lr_credits_0" value="0"
                                                        {{ old('lr_credits') == 0 ? 'checked' : '' }}
                                                        onchange="paymentDetailsUpdate()">
                                                    <label class="form-check-label" for="lr_credits_0">
                                                        None
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Annual Maintenance Fee -->
                                    <div class="col-md-6 mb-3">
                                        <div class="form-label">
                                            <strong>Annual Maintenance Fee</strong> <span class="text-danger">*</span>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="annual_maintenance"
                                                id="annual_maintenance"
                                                value="{{ $annualMaintenanceAmount[$company->company_type] }}" checked>
                                            <label class="form-check-label" for="annual_maintenance">
                                                <strong>
                                                    Rs.
                                                    {{ number_format($annualMaintenanceAmount[$company->company_type], 2) }}</strong>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Tenure -->
                                    <div class="col-md-6 mb-3">
                                        <label for="tenure" class="form-label">
                                            <strong>Tenure</strong> <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="tenure" name="tenure"
                                            onchange="paymentDetailsUpdate()">
                                            <option value="1" {{ old('tenure') == 1 ? 'selected' : '' }}>1 Year
                                            </option>
                                            <option value="2" {{ old('tenure') == 2 ? 'selected' : '' }}>2 Years
                                            </option>
                                            <option value="3" {{ old('tenure') == 3 ? 'selected' : '' }}>3 Years
                                                ({{ $discount }}% Discount)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            @if (!$isExpired)
                                <!-- Add on Plan (shown when want_to_renew = no) -->
                                <div class="flow-section form-section" id="addonSection">
                                    <h4 class="mb-3">
                                        <i class="fas fa-plus-circle text-success"></i> Add on Plan
                                    </h4>
                                    <div class="row">
                                        @if ($company->company_type != 'Broker')
                                            <input type="hidden" name="current_truck_count"
                                                value="{{ $company->truck_credits ?? 0 }}">
                                            <input type="hidden" name="plan_end_date"
                                                value="{{ $company->plan_end_date }}">
                                            <!-- Addon Truck Count -->
                                            <div class="col-md-6 mb-3">
                                                <label for="addon_truck_count" class="form-label">
                                                    <strong>Additional Truck</strong>
                                                    (Current: {{ $company->truck_credits ?? 0 }})
                                                </label>
                                                <input type="number" class="form-control" id="addon_truck_count"
                                                    name="addon_truck_count" value="{{ old('addon_truck_count', 0) }}"
                                                    min="0" onchange="paymentDetailsUpdate()">
                                                <small class="text-muted">Enter number of additional trucks</small>
                                            </div>

                                            <!-- Truck Rate Details -->
                                            <div class="col-md-6 mb-3">
                                                <div class="form-label">
                                                    <strong>Truck Rate Details (Annually)</strong>
                                                </div>
                                                <ul>
                                                    @foreach ($truck_rate as $count => $rate)
                                                        <li>
                                                            {{ $count }} Trucks (Rs.
                                                            {{ number_format($rate, 2) }})
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        @if ($company->company_type != 'Agency')
                                            <!-- Addon LR Credits -->
                                            <div class="col-md-12 mb-3">
                                                <label for="addon_lr_credits" class="form-label">
                                                    <strong>LR Credits</strong>
                                                </label>
                                                <div class="form-group">
                                                    @foreach ($lr_credits as $credits => $rate)
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio"
                                                                name="addon_lr_credits"
                                                                {{ old('addon_lr_credits') == $credits ? 'checked' : '' }}
                                                                id="addon_lr_credits_{{ $credits }}"
                                                                value="{{ $credits }}"
                                                                onchange="paymentDetailsUpdate()">
                                                            <label class="form-check-label"
                                                                for="addon_lr_credits_{{ $credits }}">
                                                                {{ $credits }} Credits (Rs.
                                                                {{ number_format($rate, 2) }})
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            name="addon_lr_credits" id="addon_lr_credits_0"
                                                            {{ old('addon_lr_credits') == 0 ? 'checked' : '' }}
                                                            value="0" onchange="paymentDetailsUpdate()">
                                                        <label class="form-check-label" for="addon_lr_credits_0">
                                                            None
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Promo Code Section with validation -->
                            <div class="row mt-4">
                                <div class="col-md-6 mb-3">
                                    <label for="promo_code" class="form-label">
                                        <strong>Promo Code</strong>
                                    </label>
                                    <input type="text" class="form-control" id="promo_code" name="promo_code"
                                        onchange="applyPromoCode()" value="{{ old('promo_code', '') }}"
                                        placeholder="Enter promo code if any">
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-brand-primary" onclick="applyPromoCode()">
                                        <i class="fas fa-tag"></i> Apply Promo Code
                                    </button>
                                </div>
                                <div class="col-12" id="promo-code-message">
                                    <!-- Promo code validation message will appear here -->
                                </div>
                            </div>

                            <!-- Payment Summary -->
                            <div class="row mt-4">
                                <div class="col-12 text-end" id="payment-breakup">
                                    <!-- Payment breakdown will be displayed here -->
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-brand-primary btn-lg">
                                        <i class="fas fa-check"></i> Process Payment
                                    </button>
                                    <a href="{{ route('payment.index') }}" class="btn btn-back btn-lg">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        let promoCodeApplied = false;
        let discountType = null;
        let discountValue = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Initial payment details update
            toggleRenewalOptions();
            applyPromoCode();
        });

        function toggleRenewalOptions() {
            const renewal = document.getElementById('want_to_renew_yes')?.checked;
            const addon = document.getElementById('want_to_renew_no')?.checked;

            const renewalSection = document.getElementById('renewalSection');
            const addonSection = document.getElementById('addonSection');

            if (renewal) {
                renewalSection.classList.add('active');
                addonSection.classList.remove('active');
            }
            if (addon) {
                renewalSection.classList.remove('active');
                addonSection.classList.add('active');
            }
            paymentDetailsUpdate();
        }

        function paymentDetailsUpdate() {
            const isRenewal = document.getElementById('want_to_renew_yes')?.checked ||
                Boolean({{ $isExpired ? 'true' : 'false' }});
            let is_broker = false;
            let is_agency = false;
            @if ($company->company_type != 'Broker')
                const truckRate = [];
                const rateData = @json($truck_rate);
                for (const [count, rate] of Object.entries(rateData)) {
                    if (count.includes('+')) {
                        truckRate.push({
                            min: parseInt(count.replace('+', '')) + 1,
                            max: Infinity,
                            rate: rate
                        });
                    } else if (count.includes('-')) {
                        const [min, max] = count.split('-').map(Number);
                        truckRate.push({
                            min,
                            max,
                            rate
                        });
                    }
                }
                truckRate.sort((a, b) => a.min - b.min);
                is_agency = true;
            @endif
            @if ($company->company_type != 'Agency')
                const lrCreditsRate = @json($lr_credits);
                is_broker = true;
            @endif

            let lrCreditsCost = 0;
            let truckCost = 0;
            let totalAmount = 0;
            let annualMaintenanceCost = 0;
            const discount = {{ $discount }};
            const serviceGstPercentage = {{ $serviceGstPercentage }};

            if (isRenewal) {
                const tenure = parseInt(document.getElementById('tenure')?.value || 1);

                if (is_agency) {
                    // Calculate Truck Cost
                    const truckCount = parseInt(document.getElementById('truck_count')?.value || 0);
                    let remainingTrucks = truckCount;

                    for (let slab of truckRate) {
                        if (remainingTrucks <= 0) break;

                        let slabSize = slab.max === Infinity ?
                            remainingTrucks :
                            (slab.max - slab.min + 1);

                        let trucksInThisSlab = Math.min(remainingTrucks, slabSize);

                        truckCost += trucksInThisSlab * slab.rate;
                        remainingTrucks -= trucksInThisSlab;
                    }

                    truckCost = tenure == 3 ? (truckCost * tenure) * (1 - discount / 100) : truckCost * tenure;
                }
                if (is_broker) {
                    // Calculate LR Credits Cost
                    const lrCredits = parseInt(
                        document.querySelector('input[name="lr_credits"]:checked')?.value || 0
                    );
                    lrCreditsCost = (lrCreditsRate[lrCredits] || 0);
                }

                // Calculate Annual Maintenance Cost
                const annualMaintenance = parseFloat(
                    document.querySelector('input[name="annual_maintenance"]:checked')?.value || 0
                );
                annualMaintenanceCost = tenure == 3 ? (annualMaintenance * tenure) * (1 - discount / 100) :
                    annualMaintenance * tenure;
            } else {
                // Addon Plan Calculation
                if (is_broker) {
                    // Calculate LR Credits Cost
                    const lrCredits = parseInt(
                        document.querySelector('input[name="addon_lr_credits"]:checked')?.value || 0
                    );
                    lrCreditsCost = (lrCreditsRate[lrCredits] || 0);
                }
                if (is_agency) {
                    // Calculate Truck Cost
                    const addonTruckCount = parseInt(document.getElementById('addon_truck_count')?.value || 0);
                    const currentTruckCount = parseInt(
                        document.querySelector('input[name="current_truck_count"]')?.value || 0
                    );
                    const planEndDate = new Date(document.querySelector('input[name="plan_end_date"]')?.value);
                    planEndDate.setHours(23, 59, 59, 999);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    let remainingDays = Math.ceil((planEndDate - today) / (1000 * 60 * 60 * 24));
                    if (remainingDays < 0) remainingDays = 0;
                    const tenure = remainingDays / 365;

                    for (let i = 1; i <= addonTruckCount; i++) {
                        const truckNumber = currentTruckCount + i;

                        for (let slab of truckRate) {
                            if (truckNumber >= slab.min && truckNumber <= slab.max) {
                                truckCost += slab.rate;
                                break;
                            }
                            if (slab.max === Infinity && truckNumber >= slab.min) {
                                truckCost += slab.rate;
                                break;
                            }
                        }
                    }
                    truckCost = truckCost * tenure;
                }
            }

            totalAmount = truckCost + lrCreditsCost + annualMaintenanceCost;
            // Display breakdown (optional)
            // Build a clean table-based payment summary
            let breakdownHtml = '';
            if (totalAmount > 0) {
                breakdownHtml = `
                    <h5 class="mb-3"><strong>Payment Breakdown</strong></h5>
                    <table class="table table-bordered text-start w-50 ms-auto">
                        <tbody>
                `;

                // Truck Cost
                if (truckCost > 0) {
                    breakdownHtml += `
                        <tr>
                            <td><strong>Truck Cost</strong></td>
                            <td class="text-end">Rs. ${truckCost.toFixed(2)}</td>
                        </tr>
                    `;
                }

                // LR Credits Cost
                if (lrCreditsCost > 0) {
                    breakdownHtml += `
                        <tr>
                            <td><strong>LR Credits Cost</strong></td>
                            <td class="text-end">Rs. ${lrCreditsCost.toFixed(2)}</td>
                        </tr>
                    `;
                }

                // Annual Maintenance Cost
                if (annualMaintenanceCost > 0) {
                    breakdownHtml += `
                        <tr>
                            <td><strong>Annual Maintenance Cost</strong></td>
                            <td class="text-end">Rs. ${annualMaintenanceCost.toFixed(2)}</td>
                        </tr>
                    `;
                }

                // Total Amount before GST
                breakdownHtml += `
                        <tr class="table-secondary">
                            <td><strong>Subtotal</strong></td>
                            <td class="text-end">Rs. ${totalAmount.toFixed(2)}</td>
                        </tr>
                `;

                // Apply Promo Code Discount
                if (promoCodeApplied) {
                    let discountAmount = 0;
                    if (discountType === 'percentage') {
                        discountAmount = (totalAmount * discountValue) / 100;
                    } else {
                        discountAmount = discountValue;
                    }
                    if (discountAmount < totalAmount) {
                        totalAmount -= discountAmount;
                        breakdownHtml += `
                        <tr>
                            <td><strong>Promo Code Discount</strong></td>
                            <td class="text-end">- Rs. ${discountAmount.toFixed(2)}</td>
                            </tr>
                            `;

                        // Subtotal after Discount
                        breakdownHtml += `
                            <tr class="table-secondary">
                                <td><strong>Subtotal after Discount</strong></td>
                                <td class="text-end">Rs. ${totalAmount.toFixed(2)}</td>
                                </tr>
                                `;
                    }
                }

                // GST Calculation
                const gstAmount = (totalAmount * serviceGstPercentage) / 100;
                totalAmount += gstAmount;
                breakdownHtml += `
                        <tr>
                            <td><strong>GST (${serviceGstPercentage}%)</strong></td>
                            <td class="text-end">Rs. ${gstAmount.toFixed(2)}</td>
                        </tr>
                `;

                // Final Total
                breakdownHtml += `
                    <tr class="table-success">
                        <td><strong>Total Amount</strong></td>
                        <td class="text-end"><strong>Rs. ${totalAmount.toFixed(2)}</strong></td>
                    </tr>
                `;

                breakdownHtml += `
                    </tbody>
                </table>
                `;
            } else {
                breakdownHtml = '<p class="text-muted"><em>No payment required based on the selected options.</em></p>';
            }
            document.getElementById('payment-breakup').innerHTML = breakdownHtml;
        }

        function applyPromoCode() {
            const promoCode = document.getElementById('promo_code').value.trim();
            const messageDiv = document.getElementById('promo-code-message');

            if (promoCode === '') {
                promoCodeApplied = false;
                return;
            }

            $.ajax({
                url: '{{ route('promo_codes.validate') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    code: promoCode,
                },
                success: function(response) {
                    discountType = response.discount_type;
                    discountValue = parseFloat(response.discount_value);
                    promoCodeApplied = true;
                    messageDiv.innerHTML = `
                        <div class="text-success">
                            Promo code <strong>${response.code}</strong> applied successfully!.
                            You get a
                            ${discountType === 'percentage' ? discountValue + '%' : 'Rs. ' + discountValue} discount.
                        </div>
                    `;
                    paymentDetailsUpdate();
                },
                error: function(xhr, status, error) {
                    promoCodeApplied = false;
                    let errorMessage = 'An error occurred while applying the promo code.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    }
                    messageDiv.innerHTML = `<div class="text-danger">${errorMessage}</div>`;
                    paymentDetailsUpdate();
                }
            });
        }

        function validateForm() {
            const isRenewal = document.getElementById('want_to_renew_yes')?.checked ||
                Boolean({{ $isExpired ? 'true' : 'false' }});

            if (!isRenewal && !document.getElementById('want_to_renew_no')?.checked) {
                alert('Please select whether you want to renew or add features.');
                return false;
            }

            if (isRenewal) {
                @if ($company->company_type != 'Broker')
                    const truckCount = document.getElementById('truck_count')?.value;
                    if (!truckCount || truckCount < 1) {
                        alert('Please enter a valid truck count.');
                        return false;
                    }
                @endif

                @if ($company->company_type != 'Agency')
                    const lrCredits = document.querySelector('input[name="lr_credits"]:checked')?.value;
                    if (!lrCredits) {
                        alert('Please select LR credits.');
                        return false;
                    }
                @endif
            }

            return true;
        }

        function processPayment(event) {
            event.preventDefault();

            if (!validateForm()) {
                return false;
            }

            // Disable submit button
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Get form data
            const formData = new FormData(event.target);

            // Submit form via AJAX to create Razorpay order
            fetch('{{ route('payment.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Initialize Razorpay checkout
                        const options = {
                            key: data.key,
                            amount: data.amount,
                            currency: 'INR',
                            name: data.name,
                            description: data.description,
                            order_id: data.order_id,
                            prefill: data.prefill,
                            theme: data.theme,
                            handler: function(response) {
                                // Payment successful
                                handlePaymentSuccess(response);
                            },
                            modal: {
                                ondismiss: function() {
                                    // Payment cancelled
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalText;
                                }
                            }
                        };

                        const razorpay = new Razorpay(options);
                        razorpay.open();
                    } else {
                        console.error('Error creating payment order:', data);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert('Failed to create payment order. Please try again.');
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });

            return false;
        }

        function handlePaymentSuccess(response) {
            // Create a form to submit payment details to callback
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('payment.callback') }}';

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            // Add Razorpay response data
            const orderIdInput = document.createElement('input');
            orderIdInput.type = 'hidden';
            orderIdInput.name = 'razorpay_order_id';
            orderIdInput.value = response.razorpay_order_id;
            form.appendChild(orderIdInput);

            const paymentIdInput = document.createElement('input');
            paymentIdInput.type = 'hidden';
            paymentIdInput.name = 'razorpay_payment_id';
            paymentIdInput.value = response.razorpay_payment_id;
            form.appendChild(paymentIdInput);

            const signatureInput = document.createElement('input');
            signatureInput.type = 'hidden';
            signatureInput.name = 'razorpay_signature';
            signatureInput.value = response.razorpay_signature;
            form.appendChild(signatureInput);

            // Submit form
            document.body.appendChild(form);
            form.submit();
        }
    </script>
@endpush
