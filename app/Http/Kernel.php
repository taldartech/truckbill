protected $routeMiddleware = [
    // ... other middleware
    'auth' => \App\Http\Middleware\EnsureUserIsAuthenticated::class,
    
    'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'moderator' => \App\Http\Middleware\ModeratorMiddleware::class,
    'accountant' => \App\Http\Middleware\AccountantMiddleware::class,
];