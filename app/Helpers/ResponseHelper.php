<?php


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('responseHelper')) {
    function responseHelper(string $message = null, int $statusCode = 200, $resource = null)
    {
        $is_application = (bool) request()->query('is_application', false);

        \Log::info('is_application: ' . $is_application);
        return response()->json($is_application ? ($resource ?? ($message ?? '')) : [
            'success' => $statusCode,
            'message' => __($message),
            'data' => $resource,
        ], $statusCode);
    }
}
