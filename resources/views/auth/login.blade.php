<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Transport System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192"  href="{{ asset('favicon/android-icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
    <!-- End Favicon -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            /* Premium Light Gradient: Soft Indigo to Cyan */
            background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            color: #1e293b;
        }
        .login-card {
            /* Frosted Glass Effect - More Transparent */
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            /* Softer, colored shadow to blend */
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        .brand-logo {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b; /* Dark text for contrast */
            margin-bottom: 0.5rem;
            letter-spacing: -0.03em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .brand-subtitle {
            color: #475569;
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
            font-weight: 500;
        }

        .form-label {
            color: #334155;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: #1e293b;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.9);
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            color: #0f172a;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-right: none;
            color: #475569;
            border-radius: 12px 0 0 12px;
        }

        .input-group .form-control {
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .btn-login {
            background: #1e293b; /* Dark button for high contrast */
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.25);
        }

        .btn-login:hover {
            background: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 41, 59, 0.3);
        }

        .form-check-input {
            border-color: #94a3b8;
        }

        .form-check-input:checked {
            background-color: #1e293b;
            border-color: #1e293b;
        }

        .form-check-label {
            color: #475569;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(240, 253, 244, 0.8);
            border: 1px solid #bbf7d0;
            color: #15803d;
            border-radius: 12px;
            backdrop-filter: blur(5px);
        }

        .invalid-feedback, .text-danger {
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container d-flex justify-content-center">
        <div class="login-card">
            <div class="text-center">
                <img src="/logo.png" alt="TruckBill Logo" class="mb-3" style="max-height: 80px;">
                <div class="brand-logo">TruckBill</div>
                <p class="brand-subtitle">Welcome back! Please sign in to continue.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success mb-4" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="/login">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email"
                               class="form-control @if ($errors->has('email')) is-invalid @endif"
                               id="email"
                               name="email"
                               required
                               value="{{ old('email') }}"
                               placeholder="name@company.com"
                               autofocus>
                    </div>
                    @if ($errors->has('email'))
                        <div class="text-danger mt-1 small">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password"
                               class="form-control @if ($errors->has('password')) is-invalid @endif"
                               id="password"
                               name="password"
                               required
                               placeholder="Enter your password">
                    </div>
                    @if ($errors->has('password'))
                        <div class="text-danger mt-1 small">{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <div>
                        <a href="{{ route('password.request') }}" class="text-decoration-none fw-semibold" style="color: #1e293b;">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    Sign In <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
