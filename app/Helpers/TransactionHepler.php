<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('handleTransaction')) {
    function handleTransaction(callable $callback, string $successMessage = '', $resource = null, int $statusCode = 200, bool $inline_request = false)
    {
        $is_application = (bool) request()->query('is_application', false);
        \Log::info('Transaction is_application: ' . $is_application);

        try {
            $result = DB::transaction($callback);

            $data = $result;
            if ($resource) {
                if (is_string($resource) && class_exists($resource)) {
                    $data = $resource::make($result);
                } elseif ($resource instanceof \Illuminate\Http\Resources\Json\JsonResource) {
                    $data = $resource;
                }
            }

            $responseArray = [
                'success' => true,
                'status_code' => $statusCode,
                'message' => __($successMessage ?: 'Operation successful.'),
                'data' => $data,
            ];

            return response()->json($responseArray);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            $errorResponse = [
                'success' => false,
                'status_code' => 403,
                'message' => __('Operation failed.'),
                'error' => $e->getMessage(),
            ];

            return response()->json($errorResponse);
        }
    }

}
