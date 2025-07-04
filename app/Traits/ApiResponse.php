<?php

namespace App\Traits;
trait ApiResponse
{
    public function sendResponse($result, $message, $token = null, $code = 200): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        // pagination যুক্ত করার জন্য check
        if ($result instanceof \Illuminate\Pagination\LengthAwarePaginator ||
            $result instanceof \Illuminate\Pagination\Paginator) {

            $response['data'] = $result->items();  // শুধু পেজে থাকা items গুলো
            $response['pagination'] = [
                'total'        => $result->total(),
                'per_page'     => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
                'from'         => $result->firstItem(),
                'to'           => $result->lastItem(),
            ];
        }

        // token থাকলে সেটাও যোগ করবে
        if ($token) {
            $response['access_token'] = $token;
            $response['token_type'] = 'bearer';
        }

        return response()->json($response, $code);
    }

    public function sendError(string $error, array $errorMessages = [], int $code = 404): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }
        return response()->json($response, $code);
    }
}


