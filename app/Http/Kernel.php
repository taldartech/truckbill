protected $routeMiddleware = [
    // ... other middleware
    'auth' => \App\Http\Middleware\EnsureUserIsAuthenticated::class,
];