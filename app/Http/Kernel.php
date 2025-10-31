protected $routeMiddleware = [
    // ... other middleware
    'auth' => \App\Http\Middleware\EnsureUserIsAuthenticated::class,
    'role' => \App\Http\Middleware\CheckUserRole::class,
];