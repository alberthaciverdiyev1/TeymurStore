<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('handleTransaction')) {
    function handleTransaction(callable $callback, string $successMessage = '', $resource = null, int $statusCode = 200)
    {
        try {
            $result = DB::transaction($callback);

            return response()->json([
                'success' => 201,
                'status_code' => $statusCode,
                'message' => __($successMessage ?? 'Operation successful.'),
                'data' => $resource ? $resource::make($result) : $result,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => __('Operation failed.'),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
