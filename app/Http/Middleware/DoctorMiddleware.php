<?php
namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorMiddleware
{
    use ApiResponse;
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only Doctor can Access.'), [],403);
        }

        return $next($request);
    }
}
