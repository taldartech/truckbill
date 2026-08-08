@extends('layouts.app')

@section('content')
<style>
/* Prevent form expansion on validation errors */
.form-control, .form-select {
    min-height: 38px;
    box-sizing: border-box;
}

.text-danger {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    word-wrap: break-word;
}

/* Ensure consistent column widths */
.row .col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

/* Prevent card from expanding */
.card {
    overflow: hidden;
}

/* Consistent spacing for form groups */
.mb-3 {
    margin-bottom: 1rem !important;
}
</style>

@if(session('success'))
    <div class="alert alert-success mt-2">
        {{ session('success') }}
    </div>
@endif

<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title h4">Edit Consignee/Consignor/Customer</div>
    </div>
    <!--begin::Form-->
    <form method="POST" action="{{ route('consignees.update', $consignee->id) }}" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        
        <div class="card-body">
            <!-- Basic Details -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="name" class="form-label">Consignee Name *</label>
                    <input placeholder="Enter Consignee Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name', $consignee->name) }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="contact_number" class="form-label">Contact Number *</label>
                    <input placeholder="Enter Contact Number" type="text" class="form-control" id="contact_number" name="contact_number" required value="{{ old('contact_number', $consignee->contact_number) }}">
                    @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input placeholder="Enter Email" type="email" class="form-control" id="email" name="email" value="{{ old('email', $consignee->email) }}">
                    @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                
            </div>
            
            <div class="row mb-3">
            <div class="col-md-4">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number', $consignee->gst_number) }}">
                    <div class="invalid-feedback" id="gst_number_feedback"></div>
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input placeholder="Enter PAN Number (e.g., ABCDE1234F)" type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number', $consignee->pan_number) }}" maxlength="10" style="text-transform: uppercase;">
                    <div class="invalid-feedback" id="pan_number_feedback"></div>
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                
            </div>
            <!-- Location Details -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="country_id" class="form-label">Country *</label>
                    <div class="input-group">
                        <select class="form-select" id="country_id" name="country_id" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id', $consignee->country_id) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal" title="Add New Country">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="state_id" class="form-label">State *</label>
                    <div class="input-group">
                        <select class="form-select" id="state_id" name="state_id" required>
                            <option value="">Select State</option>
                            @if(old('country_id', $consignee->country_id))
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id', $consignee->state_id) == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStateModal" title="Add New State">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="city_id" class="form-label">City *</label>
                    <div class="input-group">
                        <select class="form-select" id="city_id" name="city_id" required>
                            <option value="">Select City</option>
                            @if(old('state_id', $consignee->state_id))
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('city_id', $consignee->city_id) == $city->id ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCityModal" title="Add New City">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="address" class="form-label">Address</label>
                    <input placeholder="Enter Address" type="text" class="form-control" id="address" name="address" value="{{ old('address', $consignee->address) }}">
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
               
            </div>
        </div>
        
        <div class="card-footer">
            <button class="btn btn-success" type="submit">
                <i class="bi bi-check-circle"></i> Update Consignee
            </button>
            <a href="{{ route('consignees.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="/js/dynamic-dropdowns.js"></script>
<script>
    // Function to show success message
    function showSuccessMessage(message) {
        // Remove any existing alerts
        $('.alert-success').remove();
        
        // Create and show success alert
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                <i class="bi bi-check-circle-fill"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Insert after the page title or at the top of the form
        if ($('.card-header').length) {
            $('.card-header').after(alertHtml);
        } else {
            $('form').before(alertHtml);
        }
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut();
        }, 5000);
    }

    // Function to show error message
    function showErrorMessage(message) {
        // Remove any existing alerts
        $('.alert-danger').remove();
        
        // Create and show error alert
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Insert after the page title or at the top of the form
        if ($('.card-header').length) {
            $('.card-header').after(alertHtml);
        } else {
            $('form').before(alertHtml);
        }
    }

    $(document).ready(function() {
        // Handle country change
        $('#country_id').change(function() {
            const countryId = $(this).val();
            $('#country_id_hidden').val(countryId);
            
            if (countryId) {
                $.ajax({
                    url: '/api/states/' + countryId,
                    type: 'GET',
                    success: function(data) {
                        $('#state_id').empty().append('<option value="">Select State</option>');
                        $('#city_id').empty().append('<option value="">Select City</option>');
                        
                        data.forEach(function(state) {
                            $('#state_id').append('<option value="' + state.id + '">' + state.name + '</option>');
                        });
                    }
                });
            } else {
                $('#state_id').empty().append('<option value="">Select State</option>');
                $('#city_id').empty().append('<option value="">Select City</option>');
                $('#country_id_hidden').val('');
                $('#state_id_hidden').val('');
                $('#city_id_hidden').val('');
            }
        });

        // Handle state change
        $('#state_id').change(function() {
            const stateId = $(this).val();
            $('#state_id_hidden').val(stateId);
            
            if (stateId) {
                $.ajax({
                    url: '/api/cities/' + stateId,
                    type: 'GET',
                    success: function(data) {
                        $('#city_id').empty().append('<option value="">Select City</option>');
                        
                        data.forEach(function(city) {
                            $('#city_id').append('<option value="' + city.id + '">' + city.name + '</option>');
                        });
                    }
                });
            } else {
                $('#city_id').empty().append('<option value="">Select City</option>');
                $('#city_id_hidden').val('');
            }
        });

        // Handle city change
        $('#city_id').change(function() {
            const cityId = $(this).val();
        });

        // Modal form submissions with AJAX
        // Handle Country Modal Form Submission
        $('#addCountryModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#country_id').append(`<option value="${response.country.id}">${response.country.name}</option>`);
                        
                        // Add new country to modal dropdowns
                        $('#state_country_id').append(`<option value="${response.country.id}">${response.country.name}</option>`);
                        $('#city_country_id').append(`<option value="${response.country.id}">${response.country.name}</option>`);
                        
                        $('#country_id').val(response.country.id);
                        $('#addCountryModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#addCountryModal form')[0].reset();
                        showSuccessMessage('Country added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            showErrorMessage(errors[field][0]);
                        }
                    } else {
                        showErrorMessage('An error occurred while adding the country.');
                    }
                }
            });
        });

        // Handle State Modal Form Submission
        $('#addStateModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#state_id').append(`<option value="${response.state.id}">${response.state.name}</option>`);
                        
                        // Add new state to city modal dropdown
                        $('#city_state_id').append(`<option value="${response.state.id}">${response.state.name}</option>`);
                        
                        $('#state_id').val(response.state.id);
                        $('#addStateModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#addStateModal form')[0].reset();
                        showSuccessMessage('State added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            showErrorMessage(errors[field][0]);
                        }
                    } else {
                        showErrorMessage('An error occurred while adding the state.');
                    }
                }
            });
        });

        // Handle City Modal Form Submission
        $('#addCityModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#city_id').append(`<option value="${response.city.id}">${response.city.name}</option>`);
                        $('#city_id').val(response.city.id);
                        $('#addCityModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#addCityModal form')[0].reset();
                        showSuccessMessage('City added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            showErrorMessage(errors[field][0]);
                        }
                    } else {
                        showErrorMessage('An error occurred while adding the city.');
                    }
                }
            });
        });

        // Handle country change in city modal
        $('#city_country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#city_state_id');
            
            stateSelect.html('<option value="">Select State</option>');
            
            if (countryId) {
                $.get(`/api/states/${countryId}`)
                    .done(function(states) {
                        states.forEach(function(state) {
                            stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                        });
                    })
                    .fail(function() {
                        showErrorMessage('Error loading states');
                    });
            }
        });
    });
</script>

<!-- Add Country Modal -->
<div class="modal fade fb-pay-modal" id="addCountryModal" tabindex="-1" aria-labelledby="addCountryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('master.countries.store') }}" method="POST">
                @csrf
                <input type="hidden" name="from_modal" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCountryModalLabel">Add Country</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="country_name" class="form-label">Country Name *</label>
                            <input type="text" class="form-control" id="country_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="country_code" class="form-label">Country Code *</label>
                            <input type="text" class="form-control" id="country_code" name="code" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-brand-primary">Add Country</button>
                    <button type="button" class="btn btn-back" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add State Modal -->
<div class="modal fade fb-pay-modal" id="addStateModal" tabindex="-1" aria-labelledby="addStateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('master.states.store') }}" method="POST">
                @csrf
                <input type="hidden" name="from_modal" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStateModalLabel">Add State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="state_name" class="form-label">State Name *</label>
                            <input type="text" class="form-control" id="state_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="state_country_id" class="form-label">Country *</label>
                            <select class="form-select" id="state_country_id" name="country_id" required>
                                <option value="">Select Country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="state_code" class="form-label">Code *</label>
                            <input type="text" class="form-control" id="state_code" name="code" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Add State</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add City Modal -->
<div class="modal fade fb-pay-modal" id="addCityModal" tabindex="-1" aria-labelledby="addCityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('master.cities.store') }}" method="POST">
                @csrf
                <input type="hidden" name="from_modal" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCityModalLabel">Add City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="city_name" class="form-label">City Name *</label>
                            <input type="text" class="form-control" id="city_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="city_country_id" class="form-label">Country *</label>
                            <select class="form-select" id="city_country_id" name="country_id" required>
                                <option value="">Select Country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="city_state_id" class="form-label">State *</label>
                            <select class="form-select" id="city_state_id" name="state_id" required>
                                <option value="">Select State</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Add City</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Function to show success message
        function showSuccessMessage(message) {
            // Remove any existing alerts
            $('.alert-success').remove();
            
            // Create and show success alert
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.alert-success').fadeOut();
            }, 5000);
        }

        // Function to show error message
        function showErrorMessage(message) {
            // Remove any existing alerts
            $('.alert-danger').remove();
            
            // Create and show error alert
            const alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }
        }

        // Handle country change
        $('#country_id').change(function() {
            const countryId = $(this).val();
            
            if (countryId) {
                $.ajax({
                    url: '/api/states/' + countryId,
                    type: 'GET',
                    success: function(data) {
                        $('#state_id').empty().append('<option value="">Select State</option>');
                        $('#city_id').empty().append('<option value="">Select City</option>');
                        
                        data.forEach(function(state) {
                            $('#state_id').append('<option value="' + state.id + '">' + state.name + '</option>');
                        });
                    }
                });
            } else {
                $('#state_id').empty().append('<option value="">Select State</option>');
                $('#city_id').empty().append('<option value="">Select City</option>');
            }
        });

        // Handle state change
        $('#state_id').change(function() {
            const stateId = $(this).val();
            
            if (stateId) {
                $.ajax({
                    url: '/api/cities/' + stateId,
                    type: 'GET',
                    success: function(data) {
                        $('#city_id').empty().append('<option value="">Select City</option>');
                        
                        data.forEach(function(city) {
                            $('#city_id').append('<option value="' + city.id + '">' + city.name + '</option>');
                        });
                    }
                });
            } else {
                $('#city_id').empty().append('<option value="">Select City</option>');
            }
        });

        // Modal form submissions with AJAX
        // Handle Country Modal Form Submission
        $('#addCountryModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#country_id').append(`<option value="${response.country.id}">${response.country.name}</option>`);
                        
                        // Add new country to modal dropdowns
                        $('#state_country_id').append(`<option value="${response.country.id}">${response.country.name}</option>`);
                        $('#city_country_id').append(`<option value="${response.country.id}">${response.country.name}</option>`);
                        
                        $('#country_id').val(response.country.id);
                        $('#addCountryModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#addCountryModal form')[0].reset();
                        showSuccessMessage('Country added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            showErrorMessage(errors[field][0]);
                        }
                    } else {
                        showErrorMessage('An error occurred while adding the country.');
                    }
                }
            });
        });

        // Handle State Modal Form Submission
        $('#addStateModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#state_id').append(`<option value="${response.state.id}">${response.state.name}</option>`);
                        
                        // Add new state to city modal dropdown
                        $('#city_state_id').append(`<option value="${response.state.id}">${response.state.name}</option>`);
                        
                        $('#state_id').val(response.state.id);
                        $('#addStateModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#addStateModal form')[0].reset();
                        showSuccessMessage('State added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            showErrorMessage(errors[field][0]);
                        }
                    } else {
                        showErrorMessage('An error occurred while adding the state.');
                    }
                }
            });
        });

        // Handle City Modal Form Submission
        $('#addCityModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#city_id').append(`<option value="${response.city.id}">${response.city.name}</option>`);
                        $('#city_id').val(response.city.id);
                        $('#addCityModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#addCityModal form')[0].reset();
                        showSuccessMessage('City added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            showErrorMessage(errors[field][0]);
                        }
                    } else {
                        showErrorMessage('An error occurred while adding the city.');
                    }
                }
            });
        });

        // Handle country change in city modal
        $('#city_country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#city_state_id');
            
            stateSelect.html('<option value="">Select State</option>');
            
            if (countryId) {
                $.get(`/api/states/${countryId}`)
                    .done(function(states) {
                        states.forEach(function(state) {
                            stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                        });
                    })
                    .fail(function() {
                        showErrorMessage('Error loading states');
                    });
            }
        });

        // Fresh approach using fetch API for modal submissions
        document.addEventListener('DOMContentLoaded', function() {
            
            // Country Modal Handler
            const countryForm = document.getElementById('addCountryForm');
            if (countryForm) {
                countryForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const errorDiv = document.getElementById('countryFormErrors');
                    
                    // Show loading
                    submitBtn.disabled = true;
                    spinner?.classList.remove('d-none');
                    errorDiv?.classList.add('d-none');
                    
                    try {
                        const formData = new FormData(this);
                        formData.append('from_modal', '1');
                        
                        const response = await fetch('{{ route("master.countries.store") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.country) {
                            // Update all dropdowns
                            updateCountryDropdowns(data.country);
                            
                            // Select new country
                            document.getElementById('country_id').value = data.country.id;
                            document.getElementById('country_id').dispatchEvent(new Event('change'));
                            
                            // Clean close modal
                            closeModalClean('addCountryModal');
                            
                            // Reset form
                            this.reset();
                        } else {
                            throw new Error(data.message || 'Failed to add country');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        if (errorDiv) {
                            errorDiv.innerHTML = error.message || 'An error occurred while adding the country.';
                            errorDiv.classList.remove('d-none');
                        }
                    } finally {
                        submitBtn.disabled = false;
                        spinner?.classList.add('d-none');
                    }
                });
            }

            // State Modal Handler
            const stateForm = document.getElementById('addStateForm');
            if (stateForm) {
                stateForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const errorDiv = document.getElementById('stateFormErrors');
                    
                    // Show loading
                    submitBtn.disabled = true;
                    spinner?.classList.remove('d-none');
                    errorDiv?.classList.add('d-none');
                    
                    try {
                        const formData = new FormData(this);
                        formData.append('from_modal', '1');
                        
                        const response = await fetch('{{ route("master.states.store") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.state) {
                            // Update dropdowns
                            updateStateDropdowns(data.state);
                            
                            // Select new state
                            document.getElementById('state_id').value = data.state.id;
                            document.getElementById('state_id').dispatchEvent(new Event('change'));
                            
                            // Clean close modal
                            closeModalClean('addStateModal');
                            
                            // Reset form
                            this.reset();
                        } else {
                            throw new Error(data.message || 'Failed to add state');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        if (errorDiv) {
                            errorDiv.innerHTML = error.message || 'An error occurred while adding the state.';
                            errorDiv.classList.remove('d-none');
                        }
                    } finally {
                        submitBtn.disabled = false;
                        spinner?.classList.add('d-none');
                    }
                });
            }

            // City Modal Handler
            const cityForm = document.getElementById('addCityForm');
            if (cityForm) {
                cityForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const errorDiv = document.getElementById('cityFormErrors');
                    
                    // Show loading
                    submitBtn.disabled = true;
                    spinner?.classList.remove('d-none');
                    errorDiv?.classList.add('d-none');
                    
                    try {
                        const formData = new FormData(this);
                        formData.append('from_modal', '1');
                        
                        const response = await fetch('{{ route("master.cities.store") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.city) {
                            // Update dropdown
                            updateCityDropdown(data.city);
                            
                            // Select new city
                            document.getElementById('city_id').value = data.city.id;
                            document.getElementById('city_id').dispatchEvent(new Event('change'));
                            
                            // Clean close modal
                            closeModalClean('addCityModal');
                            
                            // Reset form
                            this.reset();
                        } else {
                            throw new Error(data.message || 'Failed to add city');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        if (errorDiv) {
                            errorDiv.innerHTML = error.message || 'An error occurred while adding the city.';
                            errorDiv.classList.remove('d-none');
                        }
                    } finally {
                        submitBtn.disabled = false;
                        spinner?.classList.add('d-none');
                    }
                });
            }
            
            // Note: The country, state, and city cascading selects are handled by dynamic-dropdowns.js
            // No need for duplicate event listeners here
            
            // GST and PAN number validation
            const gstNumberInput = document.getElementById('gst_number');
            const panNumberInput = document.getElementById('pan_number');
            const gstFeedback = document.getElementById('gst_number_feedback');
            const panFeedback = document.getElementById('pan_number_feedback');
            let gstTimeout = null;
            let panTimeout = null;
            let gstValid = true;
            let panValid = true;
            const consigneeId = {{ $consignee->id }}; // Current consignee ID for exclusion
            
            // Form validation
            const consigneeForm = document.getElementById('consigneeForm');
            if (consigneeForm) {
                consigneeForm.addEventListener('submit', function(event) {
                    // Check if GST or PAN is invalid
                    if (!gstValid || !panValid) {
                        event.preventDefault();
                        alert('Please fix the validation errors before submitting the form.');
                        return false;
                    }
                });
            }
            
            // Check GST number availability with debounce
            if (gstNumberInput) {
                gstNumberInput.addEventListener('input', function() {
                    const gstNumber = this.value.trim().toUpperCase();
                    this.value = gstNumber; // Force uppercase
                    
                    clearTimeout(gstTimeout);
                    
                    if (gstNumber.length > 0) {
                        gstTimeout = setTimeout(() => {
                            validateGstNumber(gstNumber, consigneeId);
                        }, 500);
                    } else {
                        gstNumberInput.classList.remove('is-invalid');
                        gstFeedback.textContent = '';
                        gstValid = true;
                    }
                });
            }
            
            // Check PAN number availability with debounce
            if (panNumberInput) {
                panNumberInput.addEventListener('input', function() {
                    const panNumber = this.value.trim().toUpperCase();
                    this.value = panNumber; // Force uppercase
                    
                    clearTimeout(panTimeout);
                    
                    if (panNumber.length === 10) { // Only validate complete PAN numbers
                        panTimeout = setTimeout(() => {
                            validatePanNumber(panNumber, consigneeId);
                        }, 500);
                    } else {
                        panNumberInput.classList.remove('is-invalid');
                        panFeedback.textContent = '';
                        panValid = true;
                    }
                });
            }
            
            // Validate GST number against API
            function validateGstNumber(gstNumber, excludeId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch('/api/validate/gst-number', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ 
                        gst_number: gstNumber,
                        exclude_id: excludeId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.available) {
                        gstNumberInput.classList.add('is-invalid');
                        gstFeedback.textContent = 'This GST Number is already in use';
                        gstValid = false;
                    } else {
                        gstNumberInput.classList.remove('is-invalid');
                        gstFeedback.textContent = '';
                        gstValid = true;
                    }
                })
                .catch(error => {
                    console.error('Error checking GST number:', error);
                    gstValid = true; // Default to valid on error to not block submission
                });
            }
            
            // Validate PAN number against API
            function validatePanNumber(panNumber, excludeId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch('/api/validate/pan-number', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ 
                        pan_number: panNumber,
                        exclude_id: excludeId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.available) {
                        panNumberInput.classList.add('is-invalid');
                        panFeedback.textContent = 'This PAN Number is already in use';
                        panValid = false;
                    } else {
                        panNumberInput.classList.remove('is-invalid');
                        panFeedback.textContent = '';
                        panValid = true;
                    }
                })
                .catch(error => {
                    console.error('Error checking PAN number:', error);
                    panValid = true; // Default to valid on error to not block submission
                });
            }
            
            // Handle city change with validation
            $('#city_id').on('change', function() {
                const cityId = $(this).val();
                const stateId = $('#state_id').val();
                const countryId = $('#country_id').val();
                
                // Clear validation errors
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
                
                // Additional client-side validation
                if (cityId && (!stateId || !countryId)) {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback">Please select country and state first.</div>');
                }
            });
        });

        // Helper functions for modal management
        function updateCountryDropdowns(country) {
            const dropdowns = ['country_id', 'state_country_id', 'city_country_id'];
            dropdowns.forEach(id => {
                const select = document.getElementById(id);
                if (select) {
                    const option = document.createElement('option');
                    option.value = country.id;
                    option.textContent = country.name;
                    select.appendChild(option);
                }
            });
        }
        
        function updateStateDropdowns(state) {
            const dropdowns = ['state_id', 'city_state_id'];
            dropdowns.forEach(id => {
                const select = document.getElementById(id);
                if (select) {
                    const option = document.createElement('option');
                    option.value = state.id;
                    option.textContent = state.name;
                    select.appendChild(option);
                }
            });
        }
        
        function updateCityDropdown(city) {
            const select = document.getElementById('city_id');
            if (select) {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                select.appendChild(option);
            }
        }
        
        function closeModalClean(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Use Bootstrap's modal instance
                const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                bsModal.hide();
                
                // Clean up after hide completes
                modal.addEventListener('hidden.bs.modal', function cleanup() {
                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    modal.removeEventListener('hidden.bs.modal', cleanup);
                }, { once: true });
            }
        }

    });
</script>
@endsection

