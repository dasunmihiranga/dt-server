<?php

namespace App\Http\Traits;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse($data = null, $message = null, $code = 200)
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data) {
            $response = array_merge($response, $data);
        }

        return response()->json($response, $code);
    }

    /**
     * Error response
     */
    protected function errorResponse($message, $errors = null, $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors, $message = 'Validation failed')
    {
        return $this->errorResponse($message, $errors, 422);
    }
}
