<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MasterAdminMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    $user = Auth::user();
    $masterAdminRoleId = DB::table('user_roles')->where('name', 'master_admin')->value('id');
    if ($user && $user->user_role_id === $masterAdminRoleId) {
      return $next($request);
    }
    abort(403, 'Unauthorized action.');
  }
}
