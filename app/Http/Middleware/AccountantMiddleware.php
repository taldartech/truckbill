<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
    
class AccountantMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    $user = Auth::user();
    $accountantRoleId = DB::table('user_roles')->where('name', 'accountant')->value('id');
    if (!$user || $user->user_role_id === $accountantRoleId) {
      abort(403, 'Unauthorized action.');
    }
    return $next($request);
  }
}
