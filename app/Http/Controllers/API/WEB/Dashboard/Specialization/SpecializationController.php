<?php

namespace App\Http\Controllers\API\WEB\Dashboard\Specialization;

use App\Http\Controllers\Controller;
use App\Http\Requests\WEB\Dashboard\Doctor\StoreRequest;
use App\Http\Requests\WEB\Dashboard\Specialization\StoreSpecializationRequest;
use App\Http\Requests\WEB\Dashboard\Specialization\UpdateSpecializationRequest;
use App\Http\Resources\WEB\Dashboard\Patient\PatientListResource;
use App\Http\Resources\WEB\Dashboard\Specialization\SpecializationResource;
use App\Mail\PatientAccountCreatedAdmin;
use App\Models\Patient;
use App\Models\Specialization;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SpecializationController extends Controller
{
    use ApiResponse;
    /**
     * List specializations with optional status filter
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $statusFilter = $request->input('status');

        $specializations = Specialization::when($statusFilter, function ($query) use ($statusFilter) {
            $query->where('status', $statusFilter);
        })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = SpecializationResource::collection($specializations->items());

        $pagination = [
            'total' => $specializations->total(),
            'per_page' => $specializations->perPage(),
            'current_page' => $specializations->currentPage(),
            'last_page' => $specializations->lastPage(),
            'from' => $specializations->firstItem(),
            'to' => $specializations->lastItem(),
        ];

        $analytics = $this->getSpecializationAnalytics();

        return $this->sendResponse([
            'analytics' => $analytics,
            'data' => $data,
            'pagination' => $pagination
        ], __('Specializations fetched successfully.'));
    }

    /**
     * Create a new specialization
     */
    public function store(StoreSpecializationRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        $specialization = Specialization::create($validated);

        return $this->sendResponse(new SpecializationResource($specialization), __('Specialization created successfully.'));
    }

    /**
     * Update an existing specialization
     */
    public function update(UpdateSpecializationRequest $request, Specialization $specialization): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        $specialization->update($validated);

        return $this->sendResponse(new SpecializationResource($specialization), __('Specialization updated successfully.'));
    }

    /**
     * Delete a specialization
     */
    public function destroy(Specialization $specialization): \Illuminate\Http\JsonResponse
    {
        $specialization->delete();

        return $this->sendResponse([], __('Specialization deleted successfully.'));
    }

    /**
     * Analytics for specializations
     */
    private function getSpecializationAnalytics(): array
    {
        $total = Specialization::count();
        $active = Specialization::where('status', 'active')->count();
        $inactive = Specialization::where('status', 'inactive')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive
        ];
    }
}
