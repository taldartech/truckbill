@extends('layouts.auth')

@section('title', 'Reset Password - TruckBill')

@push('styles')
    <style>
        /* Password Strength Meter */
        .strength-meter {
            height: 4px;
            background-color: #eee;
            margin-top: 10px;
            border-radius: 2px;
            overflow: hidden;
            display: flex;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            text-align: right;
            font-weight: 600;
            color: #64748b;
        }

        .requirements-list {
            list-style: none;
            margin-top: 10px;
            font-size: 12px;
            color: #64748b;
            display: none; /* Hidden by default, shown on focus */
        }
        .requirements-list.show {
            display: block;
        }

        .requirement-item {
            margin-bottom: 4px;
        }

        .requirement-item i {
            margin-right: 5px;
            width: 12px;
        }

        .requirement-item.valid {
            color: #15803d;
        }

        .requirement-item.valid i::before {
            content: "\f00c"; /* check */
        }

        .requirement-item i::before {
            content: "\f111"; /* circle */
            font-size: 8px;
            vertical-align: middle;
        }

        .btn-auth:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-auth:hover:not(:disabled) {
            transform: translateY(-2px);
        }
    </style>
@endpush

@section('content')
    <h2 class="page-title">Set New Password</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="list-style: none; padding: 0; margin: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" id="resetForm">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <input type="email" class="form-control" id="email" name="email" value="{{ $email ?? old('email') }}" required autofocus readonly style="background-color: rgba(255, 255, 255, 0.4); cursor: not-allowed; color: #64748b;">
            </div>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter new password">
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="far fa-eye" id="toggle-icon-password"></i>
                </button>
            </div>

            <div class="strength-meter">
                <div class="strength-bar" id="strength-bar"></div>
            </div>
            <div class="strength-text" id="strength-text"></div>

            <ul class="requirements-list" id="requirements-list">
                <li class="requirement-item" data-rule="length"><i class="fas"></i> At least 8 characters</li>
                <li class="requirement-item" data-rule="uppercase"><i class="fas"></i> Contains an uppercase letter</li>
                <li class="requirement-item" data-rule="lowercase"><i class="fas"></i> Contains a lowercase letter</li>
                <li class="requirement-item" data-rule="number"><i class="fas"></i> Contains a number</li>
                <li class="requirement-item" data-rule="special"><i class="fas"></i> Contains a special character</li>
            </ul>
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Confirm new password">
                <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                    <i class="far fa-eye" id="toggle-icon-password_confirmation"></i>
                </button>
                <!-- Match Indicator -->
                <span id="match-indicator" style="position: absolute; right: 45px; top: 50%; transform: translateY(-50%); display: none;">
                    <i class="fas fa-check-circle" style="color: #15803d;"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn-auth" id="submitBtn" disabled>Reset Password</button>

        <a href="{{ route('login') }}" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const requirementsList = document.getElementById('requirements-list');
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const matchIndicator = document.getElementById('match-indicator');
            const submitBtn = document.getElementById('submitBtn');

            // Show requirements on focus
            passwordInput.addEventListener('focus', () => {
                requirementsList.classList.add('show');
            });

            // Password Strength Logic
            function validatePassword() {
                const val = passwordInput.value;
                let score = 0;

                // Rules check
                const rules = {
                    length: val.length >= 8,
                    uppercase: /[A-Z]/.test(val),
                    lowercase: /[a-z]/.test(val),
                    number: /\d/.test(val),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(val)
                };

                // Update UI for requirements
                for (const [key, passed] of Object.entries(rules)) {
                    const item = document.querySelector(`.requirement-item[data-rule="${key}"]`);
                    if (passed) {
                        item.classList.add('valid');
                        score++;
                    } else {
                        item.classList.remove('valid');
                    }
                }

                // Calculate width and color
                let width = (score / 5) * 100;
                let color = '#e74c3c'; // Weak
                let text = 'Weak';

                if (score === 3 || score === 4) {
                    color = '#f1c40f'; // Medium
                    text = 'Medium';
                } else if (score === 5) {
                    color = '#2ecc71'; // Strong
                    text = 'Strong';
                }

                if (val.length === 0) {
                    width = 0;
                    text = '';
                }

                strengthBar.style.width = `${width}%`;
                strengthBar.style.backgroundColor = color;
                strengthText.textContent = text;
                strengthText.style.color = color;

                // Check if all requirements are met and passwords match
                const allRequirementsMet = score === 5;
                const passwordsMatch = confirmInput.value.length > 0 && confirmInput.value === val;

                // Enable submit button only if all requirements met and passwords match
                submitBtn.disabled = !(allRequirementsMet && passwordsMatch);

                checkMatch();
            }

            passwordInput.addEventListener('input', validatePassword);

            // Match Logic
            confirmInput.addEventListener('input', function() {
                checkMatch();
                validatePassword(); // Re-validate to update submit button state
            });

            function checkMatch() {
                if (confirmInput.value.length > 0 && confirmInput.value === passwordInput.value) {
                    matchIndicator.style.display = 'block';
                    confirmInput.style.borderColor = '#15803d'; // Success green
                } else {
                    matchIndicator.style.display = 'none';
                    if (confirmInput.value.length > 0) {
                         confirmInput.style.borderColor = '#ef4444'; // Danger red
                    } else {
                        confirmInput.style.borderColor = 'rgba(255, 255, 255, 0.5)'; // Border color
                    }
                }
            }
        });
    </script>
@endpush
