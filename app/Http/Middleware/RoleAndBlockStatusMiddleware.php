<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleAndBlockStatusMiddleware
{
/**
* Handle an incoming request.
*
* @param  \Illuminate\Http\Request  $request
* @param  \Closure  $next
* @param  mixed  ...$roles
* @param  bool  $checkBlockStatus
* @return mixed
*/
public function handle(Request $request, Closure $next, $checkBlockStatus = true, ...$roles)
{
$user = Auth::guard('api')->user();

// Ensure the user is authenticated
if (!$user) {
return response()->json(['message' => 'Unauthorized'], 401);
}

// Check if the user is blocked, but only if the $checkBlockStatus is true
if ($checkBlockStatus === 'true' && $user->block_status) {
return response()->json(['message' => 'Your account is blocked.'], 403);
}

// Check if the user has one of the allowed roles
if (!in_array($user->role_id, $roles)) {
return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
}

return $next($request);
}
}