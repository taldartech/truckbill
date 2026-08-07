# Transporter Portal Implementation Plan

## Overview
Implement a read-only portal for transporters to view their own records (LRs, Freight Bookings, Payments, etc.) through a separate authentication system.

---

## Implementation Approach

### **Option A: Extend User Model (Recommended)**
Add authentication fields to Transporter model and create a separate guard/provider.

**Pros:**
- Clean separation from company users
- Reuses existing authentication infrastructure
- Easy to manage transporter-specific permissions
- Can leverage Laravel's built-in auth features

**Cons:**
- Requires custom guard configuration
- Need to handle two different user types

### **Option B: Create TransporterUser Model**
Create a separate model that links to Transporter.

**Pros:**
- Complete separation
- More flexible for future features

**Cons:**
- More complex relationship management
- Duplicate authentication logic

### **Option C: Add transporter_id to User Model**
Link existing users to transporters.

**Pros:**
- Minimal changes
- Reuses existing user system

**Cons:**
- Mixes company users with transporter users
- Less secure (transporters could access company data if not careful)
- Harder to maintain clear separation

---

## Recommended Implementation: Option A

### Phase 1: Database Changes

#### 1.1 Migration: Add Authentication Fields to Transporters Table
```php
Schema::table('transporters', function (Blueprint $table) {
    $table->string('email')->unique()->nullable()->after('mobile_number');
    $table->string('password')->nullable()->after('email');
    $table->boolean('can_login')->default(false)->after('password');
    $table->timestamp('email_verified_at')->nullable()->after('password');
    $table->rememberToken()->after('email_verified_at');
    $table->timestamp('last_login_at')->nullable()->after('remember_token');
});
```

#### 1.2 Migration: Create Transporter Sessions Table (Optional)
If you want separate session management:
```php
Schema::create('transporter_sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('transporter_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

---

### Phase 2: Model Updates

#### 2.1 Update Transporter Model
```php
// app/Models/Transporter.php

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Transporter extends Authenticatable
{
    use SoftDeletes, Notifiable;
    
    // Add to fillable
    protected $fillable = [
        // ... existing fields
        'email',
        'password',
        'can_login',
        'email_verified_at',
        'last_login_at',
    ];
    
    // Add to hidden
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    // Add casts
    protected $casts = [
        'can_login' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'pan_active' => 'boolean',
    ];
    
    // Add password hashing mutator
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
    
    // Helper method to check if transporter can login
    public function canAccessPortal()
    {
        return $this->can_login && !$this->trashed();
    }
}
```

---

### Phase 3: Authentication Configuration

#### 3.1 Update config/auth.php
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'transporter' => [
        'driver' => 'session',
        'provider' => 'transporters',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'transporters' => [
        'driver' => 'eloquent',
        'model' => App\Models\Transporter::class,
    ],
],
```

#### 3.2 Update bootstrap/app.php (if using Laravel 11+)
Or register middleware in `app/Http/Kernel.php` if using older Laravel:
```php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
    ],
];

protected $routeMiddleware = [
    // ... existing middleware
    'transporter.auth' => \App\Http\Middleware\TransporterAuth::class,
    'transporter.guest' => \App\Http\Middleware\TransporterGuest::class,
];
```

---

### Phase 4: Middleware

#### 4.1 Create TransporterAuth Middleware
```php
// app/Http/Middleware/TransporterAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TransporterAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('transporter')->check()) {
            return redirect()->route('transporter.login');
        }
        
        $transporter = Auth::guard('transporter')->user();
        
        // Check if transporter can access portal
        if (!$transporter->canAccessPortal()) {
            Auth::guard('transporter')->logout();
            return redirect()->route('transporter.login')
                ->withErrors(['email' => 'Your account access has been disabled.']);
        }
        
        return $next($request);
    }
}
```

#### 4.2 Create TransporterGuest Middleware
```php
// app/Http/Middleware/TransporterGuest.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TransporterGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('transporter')->check()) {
            return redirect()->route('transporter.dashboard');
        }
        
        return $next($request);
    }
}
```

---

### Phase 5: Controllers

#### 5.1 Create TransporterAuthController
```php
// app/Http/Controllers/Transporter/TransporterAuthController.php

namespace App\Http\Controllers\Transporter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Transporter;

class TransporterAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('transporter.auth.login');
    }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        
        if (Auth::guard('transporter')->attempt($credentials, $request->filled('remember'))) {
            $transporter = Auth::guard('transporter')->user();
            
            // Check if transporter can login
            if (!$transporter->canAccessPortal()) {
                Auth::guard('transporter')->logout();
                return back()->withErrors([
                    'email' => 'Your account access has been disabled.',
                ]);
            }
            
            // Update last login
            $transporter->update(['last_login_at' => now()]);
            
            $request->session()->regenerate();
            return redirect()->intended(route('transporter.dashboard'));
        }
        
        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }
    
    public function logout(Request $request)
    {
        Auth::guard('transporter')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('transporter.login');
    }
}
```

#### 5.2 Create TransporterDashboardController
```php
// app/Http/Controllers/Transporter/TransporterDashboardController.php

namespace App\Http\Controllers\Transporter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LorryReceipt;
use App\Models\FreightBooking;
use App\Models\VoucherPayment;
use App\Models\AdvancePayment;

class TransporterDashboardController extends Controller
{
    public function index()
    {
        $transporter = Auth::guard('transporter')->user();
        
        // Get statistics
        $stats = [
            'total_lrs' => LorryReceipt::where('transporter_id', $transporter->id)
                ->where('company_id', $transporter->company_id)
                ->count(),
            'pending_lrs' => LorryReceipt::where('transporter_id', $transporter->id)
                ->where('company_id', $transporter->company_id)
                ->where('delivery_status', 'Pending')
                ->count(),
            'delivered_lrs' => LorryReceipt::where('transporter_id', $transporter->id)
                ->where('company_id', $transporter->company_id)
                ->where('delivery_status', 'Delivered')
                ->count(),
            'total_payments' => VoucherPayment::whereHas('freight_booking', function($q) use ($transporter) {
                $q->where('transporter_id', $transporter->id)
                  ->where('company_id', $transporter->company_id);
            })->sum('amount'),
            'pending_payments' => VoucherPayment::whereHas('freight_booking', function($q) use ($transporter) {
                $q->where('transporter_id', $transporter->id)
                  ->where('company_id', $transporter->company_id);
            })->where('payment_status', 'pending')->sum('amount'),
        ];
        
        // Recent LRs
        $recent_lrs = LorryReceipt::where('transporter_id', $transporter->id)
            ->where('company_id', $transporter->company_id)
            ->with(['truck', 'consignor', 'consignee', 'customer'])
            ->latest()
            ->limit(10)
            ->get();
        
        return view('transporter.dashboard', compact('stats', 'recent_lrs', 'transporter'));
    }
}
```

#### 5.3 Create TransporterLorryReceiptController (Read-Only)
```php
// app/Http/Controllers/Transporter/TransporterLorryReceiptController.php

namespace App\Http\Controllers\Transporter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LorryReceipt;

class TransporterLorryReceiptController extends Controller
{
    public function index()
    {
        $transporter = Auth::guard('transporter')->user();
        
        $lrs = LorryReceipt::where('transporter_id', $transporter->id)
            ->where('company_id', $transporter->company_id)
            ->with(['truck', 'consignor', 'consignee', 'customer', 'freightBookings'])
            ->latest()
            ->paginate(20);
        
        return view('transporter.lorry_receipts.index', compact('lrs'));
    }
    
    public function show(LorryReceipt $lorryReceipt)
    {
        $transporter = Auth::guard('transporter')->user();
        
        // Ensure transporter can only view their own LRs
        if ($lorryReceipt->transporter_id !== $transporter->id || 
            $lorryReceipt->company_id !== $transporter->company_id) {
            abort(403, 'Unauthorized');
        }
        
        $lorryReceipt->load([
            'truck',
            'transporter',
            'consignor',
            'consignee',
            'customer',
            'freightBookings.voucherPayments',
            'company'
        ]);
        
        return view('transporter.lorry_receipts.show', compact('lorryReceipt'));
    }
}
```

#### 5.4 Create TransporterPaymentController (Read-Only)
```php
// app/Http/Controllers/Transporter/TransporterPaymentController.php

namespace App\Http\Controllers\Transporter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VoucherPayment;
use App\Models\AdvancePayment;

class TransporterPaymentController extends Controller
{
    public function index()
    {
        $transporter = Auth::guard('transporter')->user();
        
        // Voucher Payments
        $voucherPayments = VoucherPayment::whereHas('freight_booking', function($q) use ($transporter) {
            $q->where('transporter_id', $transporter->id)
              ->where('company_id', $transporter->company_id);
        })
        ->with(['freight_booking.lr', 'pump'])
        ->latest()
        ->paginate(20);
        
        // Advance Payments
        $advancePayments = AdvancePayment::where('transporter_id', $transporter->id)
            ->where('company_id', $transporter->company_id)
            ->latest()
            ->paginate(20);
        
        return view('transporter.payments.index', compact('voucherPayments', 'advancePayments'));
    }
    
    public function showVoucher(VoucherPayment $voucherPayment)
    {
        $transporter = Auth::guard('transporter')->user();
        
        // Verify ownership
        if ($voucherPayment->freight_booking->transporter_id !== $transporter->id ||
            $voucherPayment->company_id !== $transporter->company_id) {
            abort(403, 'Unauthorized');
        }
        
        $voucherPayment->load(['freight_booking.lr', 'pump', 'company']);
        
        return view('transporter.payments.voucher_show', compact('voucherPayment'));
    }
}
```

---

### Phase 6: Routes

#### 6.1 Create transporter routes file
```php
// routes/transporter-routes.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Transporter\TransporterAuthController;
use App\Http\Controllers\Transporter\TransporterDashboardController;
use App\Http\Controllers\Transporter\TransporterLorryReceiptController;
use App\Http\Controllers\Transporter\TransporterPaymentController;

// Guest routes
Route::middleware(['transporter.guest'])->prefix('transporter')->name('transporter.')->group(function () {
    Route::get('/login', [TransporterAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [TransporterAuthController::class, 'login']);
});

// Authenticated routes
Route::middleware(['transporter.auth'])->prefix('transporter')->name('transporter.')->group(function () {
    Route::post('/logout', [TransporterAuthController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [TransporterDashboardController::class, 'index'])->name('dashboard');
    
    // Lorry Receipts (Read-only)
    Route::get('/lorry-receipts', [TransporterLorryReceiptController::class, 'index'])->name('lorry_receipts.index');
    Route::get('/lorry-receipts/{lorryReceipt}', [TransporterLorryReceiptController::class, 'show'])->name('lorry_receipts.show');
    
    // Payments (Read-only)
    Route::get('/payments', [TransporterPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/voucher/{voucherPayment}', [TransporterPaymentController::class, 'showVoucher'])->name('payments.voucher.show');
    
    // Freight Bookings (Read-only) - can be added similarly
    // Trucks (Read-only) - can be added similarly
});
```

#### 6.2 Include in web.php or bootstrap/app.php
```php
// In routes/web.php or bootstrap/app.php
require __DIR__ . '/transporter-routes.php';
```

---

### Phase 7: Views

#### 7.1 Create Transporter Layout
```blade
{{-- resources/views/transporter/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Transporter Portal - @yield('title')</title>
    <!-- Include your CSS/JS -->
</head>
<body>
    <nav>
        <div>Transporter Portal</div>
        <div>
            <span>{{ Auth::guard('transporter')->user()->name }}</span>
            <form method="POST" action="{{ route('transporter.logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </nav>
    
    <main>
        @yield('content')
    </main>
</body>
</html>
```

#### 7.2 Create Login View
```blade
{{-- resources/views/transporter/auth/login.blade.php --}}
@extends('transporter.layouts.app')

@section('title', 'Login')

@section('content')
    <form method="POST" action="{{ route('transporter.login') }}">
        @csrf
        <div>
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div>
            <label>
                <input type="checkbox" name="remember"> Remember me
            </label>
        </div>
        <button type="submit">Login</button>
    </form>
@endsection
```

#### 7.3 Create Dashboard View
```blade
{{-- resources/views/transporter/dashboard.blade.php --}}
@extends('transporter.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Welcome, {{ $transporter->name }}</h1>
    
    <div class="stats">
        <div>Total LRs: {{ $stats['total_lrs'] }}</div>
        <div>Pending: {{ $stats['pending_lrs'] }}</div>
        <div>Delivered: {{ $stats['delivered_lrs'] }}</div>
        <div>Total Payments: ₹{{ number_format($stats['total_payments'], 2) }}</div>
        <div>Pending Payments: ₹{{ number_format($stats['pending_payments'], 2) }}</div>
    </div>
    
    <h2>Recent Lorry Receipts</h2>
    <table>
        <!-- Display recent LRs -->
    </table>
@endsection
```

---

### Phase 8: Admin Interface for Managing Transporter Access

#### 8.1 Add Fields to Transporter Edit Form
```blade
{{-- In resources/views/transporters/edit.blade.php --}}
<div>
    <label>Email (for portal login)</label>
    <input type="email" name="email" value="{{ $transporter->email }}">
</div>
<div>
    <label>Password</label>
    <input type="password" name="password" placeholder="Leave blank to keep current">
</div>
<div>
    <label>
        <input type="checkbox" name="can_login" value="1" {{ $transporter->can_login ? 'checked' : '' }}>
        Allow Portal Access
    </label>
</div>
```

#### 8.2 Update TransporterController
```php
// In update method
$data = $request->validated();

if ($request->filled('password')) {
    $data['password'] = Hash::make($request->password);
} else {
    unset($data['password']);
}

$transporter->update($data);
```

---

### Phase 9: Security Considerations

#### 9.1 Data Isolation
- Always filter by `transporter_id` AND `company_id`
- Use middleware to verify ownership on show/edit routes
- Never trust client-side IDs

#### 9.2 Rate Limiting
```php
// In routes/transporter-routes.php
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/login', [TransporterAuthController::class, 'login']);
});
```

#### 9.3 CSRF Protection
- Already handled by Laravel's CSRF middleware

#### 9.4 Password Requirements
```php
// In TransporterAuthController validation
$request->validate([
    'email' => ['required', 'email'],
    'password' => ['required', 'min:8'],
]);
```

---

### Phase 10: Additional Features (Optional)

#### 10.1 Password Reset
- Create transporter password reset functionality
- Similar to user password reset

#### 10.2 Email Notifications
- Notify transporters of new LRs assigned
- Payment status updates
- Delivery confirmations

#### 10.3 Export Functionality
- Allow transporters to export their payment history
- Export LR copies

#### 10.4 Mobile API
- Create API routes for mobile app access
- Use Laravel Sanctum for API authentication

---

## Implementation Checklist

- [ ] Phase 1: Database migrations
- [ ] Phase 2: Update Transporter model
- [ ] Phase 3: Configure authentication guards
- [ ] Phase 4: Create middleware
- [ ] Phase 5: Create controllers
- [ ] Phase 6: Define routes
- [ ] Phase 7: Create views
- [ ] Phase 8: Admin interface for managing access
- [ ] Phase 9: Security hardening
- [ ] Phase 10: Additional features (optional)

---

## Testing Checklist

- [ ] Transporter can login with email/password
- [ ] Transporter cannot login if `can_login` is false
- [ ] Transporter can only see their own LRs
- [ ] Transporter can only see their own payments
- [ ] Transporter cannot access company user routes
- [ ] Company users cannot access transporter routes
- [ ] Data isolation works correctly
- [ ] Soft-deleted transporters cannot login
- [ ] Password hashing works correctly
- [ ] Session management works correctly

---

## Migration Strategy

1. **Backup database** before running migrations
2. **Run migrations** in development first
3. **Test thoroughly** with sample transporter data
4. **Gradually enable** `can_login` for transporters
5. **Monitor** for any security issues
6. **Document** the portal URL and login process for transporters

---

## Notes

- Consider using a subdomain like `transporter.yourdomain.com` for better separation
- Add logging for transporter login attempts
- Consider 2FA for enhanced security
- Regular security audits of transporter access
- Clear documentation for transporters on how to use the portal
