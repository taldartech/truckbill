<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ModeratorMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    $user = Auth::user();
    $moderatorRoleId = DB::table('user_roles')->where('name', 'moderator')->value('id');
    $adminRoleId = DB::table('user_roles')->where('name', 'admin')->value('id');
    if ($user && in_array($user->user_role_id, [$moderatorRoleId, $adminRoleId])) {
      return $next($request);
    }
    abort(403, 'Unauthorized action.');
  }
}
