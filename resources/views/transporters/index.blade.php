@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Transporter Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Always visible container --}}
{{-- <div class="card card-body shadow-sm mb-4"> --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn btn-info" onclick="document.getElementById('advanced-search-box').classList.toggle('d-none')">
            <i class="bi bi-funnel"></i> Search filter
        </button>
        <a href="{{ route('transporters.create') }}" class="btn btn-info">
            <i class="bi bi-plus"></i> create
        </a>
    </div>
{{-- </div> --}}

{{-- Hidden filter form --}}
<form method="GET" action="{{ route('transporters.index') }}" id="advanced-search-box" class="card card-body shadow-sm mb-4 d-none">
    {{-- Filter checkboxes --}}
    <div class="row g-2">
        <div class="col-md-12">
            <label class="form-label fw-bold">Choose Filter Fields:</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="name" id="filter_name" onchange="toggleField(this)">
                <label class="form-check-label" for="filter_name">Name</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="gst_number" id="filter_gst" onchange="toggleField(this)">
                <label class="form-check-label" for="filter_gst">GST Number</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="gst_type" id="filter_gst_type" onchange="toggleField(this)">
                <label class="form-check-label" for="filter_gst_type">GST Type</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="pan_number" id="filter_pan" onchange="toggleField(this)">
                <label class="form-check-label" for="filter_pan">PAN Number</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="is_owner" id="filter_type" onchange="toggleField(this)">
                <label class="form-check-label" for="filter_type">Type</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="company_id" id="filter_company" onchange="toggleField(this)">
                <label class="form-check-label" for="filter_company">Company</label>
            </div>
        </div>
    </div>

    {{-- Conditionally visible filter fields --}}
    <div class="row g-3 mt-2" id="filter-fields">
        <div class="col-md-2 d-none" id="field_name">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ request('name') }}">
        </div>
        <div class="col-md-2 d-none" id="field_gst_number">
            <label class="form-label">GST Number</label>
            <input type="text" name="gst_number" class="form-control" value="{{ request('gst_number') }}">
        </div>
        <div class="col-md-2 d-none" id="field_gst_type">
            <label class="form-label">GST Type</label>
            <select name="gst_type" class="form-select">
                <option value="">All</option>
                <option value="composition" {{ request('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ request('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
        </div>
        <div class="col-md-2 d-none" id="field_pan_number">
            <label class="form-label">PAN Number</label>
            <input type="text" name="pan_number" class="form-control" value="{{ request('pan_number') }}">
        </div>
        <div class="col-md-2 d-none" id="field_is_owner">
            <label class="form-label">Type</label>
            <select name="is_owner" class="form-select">
                <option value="">All</option>
                <option value="1" {{ request('is_owner') === '1' ? 'selected' : '' }}>Owner</option>
                <option value="0" {{ request('is_owner') === '0' ? 'selected' : '' }}>Agency</option>
            </select>
        </div>
        <div class="col-md-2 d-none" id="field_company_id">
            <label class="form-label">Company</label>
            @php
                $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
                $userCompanyId = auth()->user()->company_id ?? null;
            @endphp
            @if($canEditCompany)
                <select name="company_id" class="form-select">
                    <option value="">All</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id', $userCompanyId) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                <div class="form-control-plaintext">
                    {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
                </div>
            @endif
        </div>
    </div>

    {{-- Form action buttons --}}
    <div class="text-end mt-3">
        <button type="submit" class="btn btn-secondary">
            <i class="bi bi-search"></i> Search
        </button>
        <a href="{{ route('transporters.index') }}" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-clockwise"></i> Reset
        </a>
    </div>
</form>


   
    
    
    {{-- <form method="GET" action="{{ route('transporters.index') }}" class="row g-3 mb-3">
        <div class="col-md-2">
            <input type="text" name="name" class="form-control" placeholder="Name" value="{{ request('name') }}">
        </div>
        <div class="col-md-2">
            <input type="text" name="gst_number" class="form-control" placeholder="GST Number" value="{{ request('gst_number') }}">
        </div>
        <div class="col-md-2">
            <select name="gst_type" class="form-select">
                <option value="">All GST Types</option>
                <option value="composition" {{ request('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ request('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="pan_number" class="form-control" placeholder="PAN Number" value="{{ request('pan_number') }}">
        </div>
        <div class="col-md-2">
            <select name="is_owner" class="form-select">
                <option value="">All Types</option>
                <option value="1" {{ request('is_owner') === '1' ? 'selected' : '' }}>Owner</option>
                <option value="0" {{ request('is_owner') === '0' ? 'selected' : '' }}>Agency</option>
            </select>
        </div>
        @php
    $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
    $userCompanyId = auth()->user()->company_id ?? null;
@endphp
<div class="col-md-2">
    @if($canEditCompany)
        <select name="company_id" class="form-select">
            <option value="">All Companies</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ request('company_id', $userCompanyId) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
            @endforeach
        </select>
    @else
        <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
        <div class="form-control-plaintext">
            {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
        </div>
    @endif
</div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i></button>
            <a href="{{ route('transporters.index') }}" class="btn btn-outline-secondary ms-2"><i class="bi bi-arrow-clockwise"></i></a>
            <a href="{{ route('transporters.create') }}" class="btn btn-primary ms-2"><i class="bi bi-plus"></i>Create</a>
        </div>
    </form> --}}
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Transporters List</h3></div>
        <!-- /.card-header -->
        <div class="card-body" style="overflow-x: auto;">
            {{-- <div class="table-responsive"> --}}
          <table class="table table-bordered">
            <thead>
              <tr>
                {{-- <th style="width: 10px">#</th> --}}
                {{-- <th>Task</th>
                <th>Progress</th>
                <th style="width: 40px">Label</th> --}}
                <th style="width: 10px">ID</th>
                <th>Name</th>
                <th>GST Number</th>
                <th>GST Type</th>
                <th>PAN Number</th>
                <th>Type</th>
                <th>Declaration Given</th>
                <th>Total Trucks</th>
                <th>Advance Amount</th>
                <th>Company</th>
                <th >Actions</th>
              </tr>
            </thead>
            <tbody>
                @forelse($transporters as $transporter)
              <tr class="align-middle">
                <td>{{ $transporter->id }}</td>
                <td>{{ $transporter->name }}</td>
                <td>{{ $transporter->gst_number }}</td>
                <td>{{ ucfirst($transporter->gst_type) }}</td>
                <td>{{ $transporter->pan_number }}</td>
                <td>{{ $transporter->is_owner ? 'Owner' : 'Agency' }}</td>
                <td>{{ $transporter->declaration_given ? 'Yes' : 'No' }}</td>
                <td>{{ $transporter->total_trucks }}</td>
                <td>{{ number_format($transporter->advance_amount, 2) }}</td>
                <td>{{ $transporter->company ? $transporter->company->name : '-' }}</td>
                <td>
                    <div class="d-inline-flex gap-1">
                    <a href="{{ route('transporters.edit', $transporter->id) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('transporters.destroy', $transporter->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"> <i class="bi bi-trash"></i></button>
                    </form>
                    </div>
                </td>
                {{-- <td>1.</td>
                <td>Update software</td>
                <td>
                  <div class="progress progress-xs">
                    <div class="progress-bar progress-bar-danger" style="width: 55%"></div>
                  </div>
                </td>
                <td><span class="badge text-bg-danger">55%</span></td>
              </tr>
              <tr class="align-middle">
                <td>2.</td>
                <td>Clean database</td>
                <td>
                  <div class="progress progress-xs">
                    <div class="progress-bar text-bg-warning" style="width: 70%"></div>
                  </div>
                </td>
                <td><span class="badge text-bg-warning">70%</span></td>
              </tr>
              <tr class="align-middle">
                <td>3.</td>
                <td>Cron job running</td>
                <td>
                  <div class="progress progress-xs progress-striped active">
                    <div class="progress-bar text-bg-primary" style="width: 30%"></div>
                  </div>
                </td>
                <td><span class="badge text-bg-primary">30%</span></td>
              </tr>
              <tr class="align-middle">
                <td>4.</td>
                <td>Fix and squish bugs</td>
                <td>
                  <div class="progress progress-xs progress-striped active">
                    <div class="progress-bar text-bg-success" style="width: 90%"></div>
                  </div>
                </td>
                <td><span class="badge text-bg-success">90%</span></td> --}}
              </tr>
              @empty
              <tr><td colspan="11" class="text-center">No transporters found.</td></tr>
          @endforelse
            </tbody>
          </table>
            {{-- </div> --}}
        </div>
        <!-- /.card-body -->
        <div class="card-footer clearfix">
          <ul class="pagination pagination-sm m-0 float-end">
            <li class="page-item"><a class="page-link" href="#">«</a></li>
            <li class="page-item"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
            <li class="page-item"><a class="page-link" href="#">»</a></li>
          </ul>
        </div>

    </div>
     
    {{-- <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>GST Number</th>
                <th>GST Type</th>
                <th>PAN Number</th>
                <th>Type</th>
                <th>Declaration Given</th>
                <th>Total Trucks</th>
                <th>Advance Amount</th>
                <th>Company</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transporters as $transporter)
                <tr>
                    <td>{{ $transporter->id }}</td>
                    <td>{{ $transporter->name }}</td>
                    <td>{{ $transporter->gst_number }}</td>
                    <td>{{ ucfirst($transporter->gst_type) }}</td>
                    <td>{{ $transporter->pan_number }}</td>
                    <td>{{ $transporter->is_owner ? 'Owner' : 'Agency' }}</td>
                    <td>{{ $transporter->declaration_given ? 'Yes' : 'No' }}</td>
                    <td>{{ $transporter->total_trucks }}</td>
                    <td>{{ number_format($transporter->advance_amount, 2) }}</td>
                    <td>{{ $transporter->company ? $transporter->company->name : '-' }}</td>
                    <td>
                        <a href="{{ route('transporters.edit', $transporter->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('transporters.destroy', $transporter->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center">No transporters found.</td></tr>
            @endforelse
        </tbody>
    </table> --}}
    {{-- <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a> --}}
</div>



<script>
    function toggleField(checkbox) {
        const fieldId = 'field_' + checkbox.value;
        const field = document.getElementById(fieldId);
        if (checkbox.checked) {
            field.classList.remove('d-none');
        } else {
            field.classList.add('d-none');
            const input = field.querySelector('input, select');
            if (input) input.value = '';
        }
    }
</script>


@endsection
