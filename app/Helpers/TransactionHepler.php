<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('handleTransaction')) {
    function handleTransaction(callable $callback, string $successMessage, $resource = null)
    {
        try {
            $result = DB::transaction($callback);

            return response()->json([
                'success' => 201,
                'message' => __($successMessage),
                'data' => $resource ? $resource::make($result) : $result,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Operation failed.'),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
