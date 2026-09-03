<?php

namespace App\Http\Controllers;

class Controller
{
    public function respondWithSuccess($message, $data = [], $statusCode = 200,$meta = null)
    {
        $response = ['success' => true, 'message' => $message, 'data' => $data, ];

        if (! empty($data)) {
            $response['data'] = $data;
        }

        if ($meta) {
            $response['meta'] = ['page' => $data->currentPage(), 'total_pages' => $data->lastPage()];
        }

        return response()->json($response, $statusCode);
    }

    public function respondWithError($message, $data = [], $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
