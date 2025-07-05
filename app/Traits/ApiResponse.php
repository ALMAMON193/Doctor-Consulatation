<?php

namespace App\Traits;
trait ApiResponse
{
    public function sendResponse($result, $message, $token = null, $code = 200): \Illuminate\Http\JsonResponse
    {
        if ($result instanceof \Illuminate\Pagination\LengthAwarePaginator || $result instanceof \Illuminate\Pagination\Paginator) {
            $response = [
                'success' => true,
                'data' => $result->items(),
                'message' => $message,
                'pagination' => [
                    'total'        => $result->total(),
                    'per_page'     => $result->perPage(),
                    'current_page' => $result->currentPage(),
                    'last_page'    => $result->lastPage(),
                    'from'         => $result->firstItem(),
                    'to'           => $result->lastItem(),
                ],
            ];
        } else {
            $response = [
                'success' => true,
                'data'    => $result,
                'message' => $message,
            ];
        }

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


