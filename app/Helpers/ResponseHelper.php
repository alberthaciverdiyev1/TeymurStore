<?php


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('responseHelper')) {
    function responseHelper(bool $is_application, string $message = null, int $statusCode = 200, $resource = null)
    {
        return response()->json($is_application ? ($resource ?? ($message ?? '')) : [
            'success' => $statusCode,
            'message' => __($message),
            'data' => $resource,
        ], $statusCode);
    }
}
