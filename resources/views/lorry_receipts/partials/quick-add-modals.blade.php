{{-- Shared Quick Add modals for Lorry Receipt create/edit --}}
@php
    $countries = $countries ?? collect();
    $truckTypes = $truckTypes ?? collect();
@endphp

{{-- Quick Add Customer --}}
<div class="modal fade fb-pay-modal" id="quickAddCustomerModal" tabindex="-1"
    aria-labelledby="quickAddCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="quickAddCustomerForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddCustomerModalLabel">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="quickCustomerFormErrors" class="alert alert-danger d-none"></div>

                    <div class="app-form-section">
                        <div class="app-form-section__header">
                            <h5 class="app-form-section__title">
                                <i class="bi bi-person-vcard"></i> Basic Details
                            </h5>
                        </div>
                        <div class="app-form-section__body">
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label for="quick_customer_name" class="form-label">Customer Name *</label>
                                    <input type="text" class="form-control" id="quick_customer_name" name="name"
                                        placeholder="Enter Customer Name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="quick_customer_contact_number" class="form-label">Contact Number *</label>
                                    <input type="text" class="form-control" id="quick_customer_contact_number"
                                        name="contact_number" placeholder="Enter Contact Number" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="quick_customer_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="quick_customer_email" name="email"
                                        placeholder="Enter Email">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label for="quick_customer_gst_number" class="form-label">GST Number</label>
                                    <input type="text" class="form-control" id="quick_customer_gst_number"
                                        name="gst_number" placeholder="Enter GST Number">
                                </div>
                                <div class="col-md-4">
                                    <label for="quick_customer_pan_number" class="form-label small fw-semibold">PAN Number</label>
                                    <input type="text" class="form-control form-control-sm text-uppercase" id="quick_customer_pan_number"
                                        name="pan_number" maxlength="10" placeholder="10-digit PAN">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="app-form-section mt-3">
                        <div class="app-form-section__header">
                            <h5 class="app-form-section__title">
                                <i class="bi bi-geo-alt"></i> Location Details
                            </h5>
                        </div>
                        <div class="app-form-section__body">
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-md-4">
                                    <label for="quick_customer_country_id" class="form-label">Country *</label>
                                    <select class="form-select" id="quick_customer_country_id" name="country_id" required>
                                        <option value="">Select Country</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label for="quick_customer_state_id" class="form-label">State *</label>
                                    <div class="input-group">
                                        <select class="form-select" id="quick_customer_state_id" name="state_id" required>
                                            <option value="">Select State</option>
                                        </select>
                                        <button type="button" class="btn btn-brand-primary px-2" id="openQuickAddStateBtn"
                                            title="Add New State">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label for="quick_customer_city_id" class="form-label">City *</label>
                                    <div class="input-group">
                                        <select class="form-select" id="quick_customer_city_id" name="city_id" required>
                                            <option value="">Select City</option>
                                        </select>
                                        <button type="button" class="btn btn-brand-primary px-2" id="openQuickAddCityBtn"
                                            title="Add New City">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-8">
                                    <label for="quick_customer_address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="quick_customer_address" name="address"
                                        placeholder="Enter Address">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Create Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Quick Add State --}}
<div class="modal fade fb-pay-modal" id="quickAddStateModal" tabindex="-1"
    aria-labelledby="quickAddStateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="quickAddStateForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddStateModalLabel">Add State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="quickStateFormErrors" class="alert alert-danger d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="quick_state_name" class="form-label">State Name *</label>
                            <input type="text" class="form-control" id="quick_state_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_state_code" class="form-label">State Code</label>
                            <input type="text" class="form-control" id="quick_state_code" name="code" maxlength="10">
                        </div>
                        <div class="col-md-12">
                            <label for="quick_state_country_id" class="form-label">Country *</label>
                            <select class="form-select" id="quick_state_country_id" name="country_id" required>
                                <option value="">Select Country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Create State
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Quick Add City --}}
<div class="modal fade fb-pay-modal" id="quickAddCityModal" tabindex="-1"
    aria-labelledby="quickAddCityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="quickAddCityForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddCityModalLabel">Add City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="quickCityFormErrors" class="alert alert-danger d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="quick_city_name" class="form-label">City Name *</label>
                            <input type="text" class="form-control" id="quick_city_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_city_country_id" class="form-label">Country *</label>
                            <select class="form-select" id="quick_city_country_id" required>
                                <option value="">Select Country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_city_state_id" class="form-label">State *</label>
                            <select class="form-select" id="quick_city_state_id" name="state_id" required>
                                <option value="">Select State</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Create City
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Quick Add Truck --}}
<div class="modal fade" id="quickAddTruckModal" tabindex="-1"
    aria-labelledby="quickAddTruckModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddTruckModalLabel">
                    <i class="bi bi-truck me-1"></i> Add New Truck
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickTruckForm">
                @csrf
                <div class="modal-body">
                    <div id="quickTruckGeneralError" class="alert alert-danger d-none"></div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="quick_truck_transporter_id" class="form-label">Truck Owner</label>
                            <select class="form-select" id="quick_truck_transporter_id" name="transporter_id"
                                data-placeholder="Search owner (type first letter)...">
                                <option value="">No Truck Owner</option>
                            </select>
                            <div class="text-danger small" id="quick_truck_transporter_id_error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_truck_number" class="form-label">Truck Number *</label>
                            <input type="text" class="form-control" id="quick_truck_number" name="truck_number"
                                placeholder="Enter Truck Number" required style="text-transform: uppercase;">
                            <div class="text-danger small" id="quick_truck_number_error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_truck_type" class="form-label">Truck Type *</label>
                            <select class="form-select" id="quick_truck_type" name="truck_type" required>
                                <option value="">Select</option>
                                @foreach ($truckTypes as $type)
                                    <option value="{{ $type->name }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-danger small" id="quick_truck_type_error"></div>
                        </div>
                        <div class="col-md-6 min-w-0">
                            <label for="quick_truck_driver_id" class="form-label">Driver</label>
                            <div class="driver-joined-group d-flex align-items-stretch">
                                <div class="flex-grow-1">
                                    <select class="form-select" id="quick_truck_driver_id" name="driver_id"
                                        data-placeholder="Search driver (type first letter)...">
                                        <option value="">No Driver</option>
                                    </select>
                                </div>
                                <a href="{{ route('drivers.create') }}" class="btn btn-secondary" target="_blank"
                                    rel="noopener">
                                    Add New Driver
                                </a>
                            </div>
                            <div class="text-danger small" id="quick_truck_driver_id_error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_truck_registration_date" class="form-label">Registration Date</label>
                            <input type="date" class="form-control" id="quick_truck_registration_date"
                                name="registration_date">
                            <div class="text-danger small" id="quick_truck_registration_date_error"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary" id="quickTruckSaveBtn">
                        <i class="bi bi-floppy-fill me-1"></i> Create Truck
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function resetSelect(select, placeholder) {
        if (!select) {
            return;
        }
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));
    }

    function appendOptions(select, items) {
        items.forEach((item) => {
            select.appendChild(new Option(item.name, item.id));
        });
    }

    function showError(box, message) {
        if (!box) {
            return;
        }
        box.textContent = message || 'Something went wrong.';
        box.classList.remove('d-none');
    }

    function hideError(box) {
        if (!box) {
            return;
        }
        box.textContent = '';
        box.classList.add('d-none');
    }

    function formatValidationErrors(data) {
        if (data?.errors && typeof data.errors === 'object') {
            return Object.values(data.errors).flat().join(' ');
        }
        return data?.message || 'Validation failed';
    }

    function closeModalClean(modalId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) {
            return;
        }

        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }

        modalElement.addEventListener('hidden.bs.modal', function () {
            const customerModal = document.getElementById('quickAddCustomerModal');
            const customerStillOpen = customerModal && customerModal.classList.contains('show');

            if (!customerStillOpen) {
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            } else {
                document.body.classList.add('modal-open');
            }
        }, { once: true });
    }

    function selectOption(select, id, label) {
        if (!select || !id) {
            return;
        }

        let option = Array.from(select.options).find((opt) => String(opt.value) === String(id));
        if (!option) {
            option = new Option(label, id, true, true);
            select.appendChild(option);
        }

        select.value = String(id);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async function fetchStates(countryId) {
        const response = await fetch(`/api/states/${countryId}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        return response.json();
    }

    async function fetchCities(stateId) {
        const response = await fetch(`/api/cities/${stateId}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        return response.json();
    }

    document.addEventListener('DOMContentLoaded', function () {
        window.__lrQuickAddCustomerTarget = null;

        const customerModalEl = document.getElementById('quickAddCustomerModal');
        if (customerModalEl) {
            customerModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const scope = trigger.closest('.lr-item, .lrf-pf, .linked-lr-card') || document;
                window.__lrQuickAddCustomerTarget =
                    scope.querySelector('.customer-select') ||
                    document.getElementById(
                        trigger.getAttribute('data-customer-select') || 'customer_id'
                    );
            });
        }

        document.addEventListener('click', function (e) {
            const openCustomerBtn = e.target.closest('.js-open-quick-add-customer');
            if (!openCustomerBtn) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const scope = openCustomerBtn.closest('.lr-item, .lrf-pf, .linked-lr-card') || document;
            window.__lrQuickAddCustomerTarget =
                scope.querySelector('.customer-select') ||
                document.getElementById(
                    openCustomerBtn.getAttribute('data-customer-select') || 'customer_id'
                );

            if (customerModalEl) {
                bootstrap.Modal.getOrCreateInstance(customerModalEl).show();
            }
        });

        const countrySelect = document.getElementById('quick_customer_country_id');
        const stateSelect = document.getElementById('quick_customer_state_id');
        const citySelect = document.getElementById('quick_customer_city_id');

        if (countrySelect && stateSelect && citySelect) {
            countrySelect.addEventListener('change', async function () {
                const countryId = this.value;
                resetSelect(stateSelect, 'Select State');
                resetSelect(citySelect, 'Select City');
                if (!countryId) {
                    return;
                }
                try {
                    appendOptions(stateSelect, await fetchStates(countryId));
                } catch (error) {
                    console.error('Error loading states:', error);
                }
            });

            stateSelect.addEventListener('change', async function () {
                const stateId = this.value;
                resetSelect(citySelect, 'Select City');
                if (!stateId) {
                    return;
                }
                try {
                    appendOptions(citySelect, await fetchCities(stateId));
                } catch (error) {
                    console.error('Error loading cities:', error);
                }
            });
        }

        document.getElementById('openQuickAddStateBtn')?.addEventListener('click', function () {
            const countryId = countrySelect?.value || '';
            const stateCountry = document.getElementById('quick_state_country_id');
            if (stateCountry) {
                stateCountry.value = countryId;
            }
            hideError(document.getElementById('quickStateFormErrors'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('quickAddStateModal')).show();
        });

        document.getElementById('openQuickAddCityBtn')?.addEventListener('click', async function () {
            const countryId = countrySelect?.value || '';
            const stateId = stateSelect?.value || '';
            const cityCountry = document.getElementById('quick_city_country_id');
            const cityState = document.getElementById('quick_city_state_id');

            if (cityCountry) {
                cityCountry.value = countryId;
            }
            resetSelect(cityState, 'Select State');
            hideError(document.getElementById('quickCityFormErrors'));

            if (countryId) {
                try {
                    appendOptions(cityState, await fetchStates(countryId));
                    if (stateId) {
                        cityState.value = stateId;
                    }
                } catch (error) {
                    console.error('Error loading states for city modal:', error);
                }
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('quickAddCityModal')).show();
        });

        document.getElementById('quick_city_country_id')?.addEventListener('change', async function () {
            const cityState = document.getElementById('quick_city_state_id');
            resetSelect(cityState, 'Select State');
            if (!this.value) {
                return;
            }
            try {
                appendOptions(cityState, await fetchStates(this.value));
            } catch (error) {
                console.error('Error loading states for city modal:', error);
            }
        });

        document.getElementById('quickAddStateForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn?.querySelector('.spinner-border');
            const errorBox = document.getElementById('quickStateFormErrors');

            hideError(errorBox);
            submitBtn.disabled = true;
            spinner?.classList.remove('d-none');

            try {
                const response = await fetch('{{ route('master.states.store') }}', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await response.json();
                if (!response.ok || !data.success || !data.state) {
                    throw new Error(formatValidationErrors(data));
                }

                const stateCountryId = String(data.state.country_id);
                if (countrySelect && String(countrySelect.value) === stateCountryId) {
                    selectOption(stateSelect, data.state.id, data.state.name);
                } else if (countrySelect) {
                    countrySelect.value = stateCountryId;
                    resetSelect(stateSelect, 'Select State');
                    resetSelect(citySelect, 'Select City');
                    appendOptions(stateSelect, await fetchStates(stateCountryId));
                    selectOption(stateSelect, data.state.id, data.state.name);
                }

                const cityState = document.getElementById('quick_city_state_id');
                const cityCountry = document.getElementById('quick_city_country_id');
                if (cityCountry && String(cityCountry.value) === stateCountryId && cityState) {
                    selectOption(cityState, data.state.id, data.state.name);
                }

                closeModalClean('quickAddStateModal');
                form.reset();
            } catch (error) {
                showError(errorBox, error.message);
            } finally {
                submitBtn.disabled = false;
                spinner?.classList.add('d-none');
            }
        });

        document.getElementById('quickAddCityForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn?.querySelector('.spinner-border');
            const errorBox = document.getElementById('quickCityFormErrors');

            hideError(errorBox);
            submitBtn.disabled = true;
            spinner?.classList.remove('d-none');

            try {
                const response = await fetch('{{ route('master.cities.store') }}', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await response.json();
                if (!response.ok || !data.success || !data.city) {
                    throw new Error(formatValidationErrors(data));
                }

                const cityStateId = String(data.city.state_id);
                if (stateSelect && String(stateSelect.value) === cityStateId) {
                    selectOption(citySelect, data.city.id, data.city.name);
                } else if (stateSelect && citySelect) {
                    if (countrySelect && data.city.country_id) {
                        countrySelect.value = String(data.city.country_id);
                    }
                    resetSelect(stateSelect, 'Select State');
                    appendOptions(stateSelect, await fetchStates(countrySelect.value));
                    selectOption(stateSelect, cityStateId, data.city.state?.name || 'Selected State');
                    resetSelect(citySelect, 'Select City');
                    appendOptions(citySelect, await fetchCities(cityStateId));
                    selectOption(citySelect, data.city.id, data.city.name);
                }

                closeModalClean('quickAddCityModal');
                form.reset();
            } catch (error) {
                showError(errorBox, error.message);
            } finally {
                submitBtn.disabled = false;
                spinner?.classList.add('d-none');
            }
        });

        const customerForm = document.getElementById('quickAddCustomerForm');
        if (customerForm) {
            customerForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitBtn = customerForm.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const errorBox = document.getElementById('quickCustomerFormErrors');

                submitBtn.disabled = true;
                spinner?.classList.remove('d-none');
                hideError(errorBox);
                errorBox.innerHTML = '';

                fetch('{{ route('consignees.store') }}', {
                    method: 'POST',
                    body: new FormData(customerForm),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(async (response) => {
                        const data = await response.json();
                        if (!response.ok) {
                            throw {
                                validation: data.errors,
                                message: data.message,
                            };
                        }
                        return data;
                    })
                    .then((data) => {
                        if (!data.success || !data.customer) {
                            return;
                        }

                        const applyCustomerOption = (select, selected) => {
                            if (!select) {
                                return;
                            }

                            const existing = Array.from(select.options).find(
                                (opt) => String(opt.value) === String(data.customer.id)
                            );
                            const option = existing || new Option(
                                data.customer.name,
                                data.customer.id,
                                selected,
                                selected
                            );

                            if (!existing) {
                                if (window.jQuery?.fn?.select2 &&
                                    window.jQuery(select).hasClass('select2-hidden-accessible')) {
                                    window.jQuery(select).append(option);
                                } else {
                                    select.add(option);
                                }
                            }

                            if (selected) {
                                if (window.jQuery?.fn?.select2 &&
                                    window.jQuery(select).hasClass('select2-hidden-accessible')) {
                                    window.jQuery(select)
                                        .val(String(data.customer.id))
                                        .trigger('change');
                                } else {
                                    select.value = String(data.customer.id);
                                    select.dispatchEvent(new Event('change'));
                                }
                            }
                        };

                        const targetSelect = window.__lrQuickAddCustomerTarget
                            || document.getElementById('customer_id');

                        applyCustomerOption(targetSelect, true);

                        // Keep other linked-row customer dropdowns and template in sync.
                        document.querySelectorAll('.customer-select').forEach((select) => {
                            if (select !== targetSelect) {
                                applyCustomerOption(select, false);
                            }
                        });

                        const templateSelect = document.querySelector(
                            '#lr-item-template .customer-select'
                        );
                        if (templateSelect && templateSelect !== targetSelect) {
                            applyCustomerOption(templateSelect, false);
                        }

                        window.__lrQuickAddCustomerTarget = null;

                        bootstrap.Modal.getInstance(
                            document.getElementById('quickAddCustomerModal')
                        )?.hide();
                        customerForm.reset();
                        hideError(errorBox);
                    })
                    .catch((error) => {
                        if (error.validation) {
                            errorBox.innerHTML = Object.values(error.validation).flat().join('<br>');
                            errorBox.classList.remove('d-none');
                        } else {
                            showError(errorBox, error.message || 'Unable to create customer.');
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        spinner?.classList.add('d-none');
                    });
            });
        }

        const quickTruckForm = document.getElementById('quickTruckForm');
        if (quickTruckForm) {
            const truckNumber = document.getElementById('quick_truck_number');
            truckNumber?.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });

            quickTruckForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const saveBtn = document.getElementById('quickTruckSaveBtn');
                const generalError = document.getElementById('quickTruckGeneralError');

                hideError(generalError);
                document.querySelectorAll('#quickTruckForm .text-danger').forEach((el) => {
                    el.textContent = '';
                });

                saveBtn.disabled = true;
                saveBtn.innerHTML = 'Creating...';

                fetch('{{ route('trucks.store') }}', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(quickTruckForm),
                })
                    .then(async (response) => {
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw { status: response.status, data };
                        }
                        return data;
                    })
                    .then((data) => {
                        const truck = data.truck;
                        const truckSelect = document.getElementById('truck_id');

                        if (truckSelect && truck) {
                            const label = truck.truck_number +
                                (truck.driver_name ? ' - Driver: ' + truck.driver_name : '') +
                                ' (' + (truck.transporter_name || 'No Truck Owner') + ')';
                            const option = new Option(label, truck.id, true, true);
                            option.dataset.transporterId = truck.transporter_id || '';
                            option.dataset.transporterName = truck.transporter_name || '';
                            option.dataset.driverName = truck.driver_name || '';
                            option.dataset.driverId = truck.driver_id || '';
                            truckSelect.appendChild(option);

                            if (window.jQuery?.fn?.select2 &&
                                window.jQuery(truckSelect).hasClass('select2-hidden-accessible')) {
                                window.jQuery(truckSelect).val(truck.id).trigger('change');
                            } else {
                                truckSelect.value = String(truck.id);
                                truckSelect.dispatchEvent(new Event('change'));
                            }
                        }

                        bootstrap.Modal.getInstance(
                            document.getElementById('quickAddTruckModal')
                        )?.hide();
                        quickTruckForm.reset();
                        if (window.jQuery) {
                            window.jQuery('#quick_truck_transporter_id').val(null).trigger('change');
                            window.jQuery('#quick_truck_driver_id').val(null).trigger('change');
                        }
                    })
                    .catch((error) => {
                        const data = error.data;
                        if (data?.errors) {
                            Object.keys(data.errors).forEach((field) => {
                                const errorElement = document.getElementById(
                                    'quick_truck_' + field + '_error'
                                );
                                if (errorElement) {
                                    errorElement.textContent = data.errors[field][0];
                                }
                            });
                        } else {
                            showError(generalError, data?.message || 'Unable to create truck. Please try again.');
                        }
                    })
                    .finally(() => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> Create Truck';
                    });
            });
        }

        function initQuickTruckFields() {
            if (!window.jQuery || typeof window.jQuery.fn.select2 === 'undefined') {
                return;
            }

            const $owner = window.jQuery('#quick_truck_transporter_id');
            const $driver = window.jQuery('#quick_truck_driver_id');

            if ($owner.length && !$owner.hasClass('select2-hidden-accessible')) {
                $owner.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    minimumInputLength: 1,
                    placeholder: 'Search owner (type first letter)...',
                    dropdownParent: window.jQuery('#quickAddTruckModal'),
                    ajax: {
                        url: '/api/truck-owners/search',
                        dataType: 'json',
                        delay: 700,
                        data: function (params) {
                            return { q: params.term || '', limit: 20 };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results || [],
                                pagination: { more: false },
                            };
                        },
                        cache: true,
                    },
                });
            }

            if ($driver.length && !$driver.hasClass('select2-hidden-accessible')) {
                $driver.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    minimumInputLength: 1,
                    placeholder: 'Search driver (type first letter)...',
                    dropdownParent: window.jQuery('#quickAddTruckModal'),
                    ajax: {
                        url: '/api/drivers/search',
                        dataType: 'json',
                        delay: 700,
                        data: function (params) {
                            return { q: params.term || '', limit: 20 };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results || [],
                                pagination: { more: false },
                            };
                        },
                        cache: true,
                    },
                });
            }
        }

        if (window.jQuery) {
            window.jQuery('#quickAddTruckModal').on('shown.bs.modal', function () {
                initQuickTruckFields();
            });
        }
    });
})();
</script>
