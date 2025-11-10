@extends('layouts.app')

@section('title', 'Change Password')
@section('content')
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Change Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/password/change">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="old_password" class="form-label">Old Password</label>
                                <input type="password" class="form-control" id="old_password" name="old_password" required />
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required />
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password_confirmation" class="form-label">Re-enter New Password</label>
                                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required />
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Update Password</button>
                            </div>
                        </form>
                        
                        @if(session('status'))
                        <div class="alert alert-success mt-3">
                            {{ session('status') }}
                        </div>
                        @endif
                        
                        @if ($errors->any())
                        <div class="alert alert-danger mt-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

