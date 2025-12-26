<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TruckBill</title>
    <link rel="icon" type="image/png" sizes="192x192"  href="{{ asset('favicon/android-icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: #1e293b;
        }
        .card-auth {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        .brand-logo {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
            letter-spacing: -0.03em;
        }
        .brand-subtitle {
            color: #475569;
            font-size: 0.95rem;
            margin-bottom: 2rem;
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
        .btn-auth {
            background: #1e293b;
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
        .btn-auth:hover {
            background: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 41, 59, 0.3);
        }
        .link-back {
            color: #1e293b;
            font-weight: 600;
            text-decoration: none;
        }
        .link-back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card-auth">
            <div class="text-center">
                <img src="/logo.png" alt="TruckBill Logo" class="mb-3" style="max-height: 80px;">
                <div class="brand-logo">TruckBill</div>
                <p class="brand-subtitle">Reset your password</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success mb-4" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autofocus>
                    @error('email')
                        <div class="text-danger mt-1 small">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-auth w-100">
                    Send reset link
                </button>
            </form>

            <div class="mt-3 text-center">
                <a class="link-back" href="{{ route('login') }}">Back to login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

