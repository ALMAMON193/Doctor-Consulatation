<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientHomeResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendError(__('Unauthorized'), [], 401);
        }
        return $this->sendResponse(new PatientHomeResource($user), __('Patient home data fetched successfully.'));
    }
}
