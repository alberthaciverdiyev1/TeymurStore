<?php


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('responseHelper')) {
    function responseHelper(string $message = null, int $statusCode = 200, $resource = null,$inline_request=false)
    {
        $is_application = $inline_request ? false : (bool) request()->query('is_application', false);

        \Log::info('is_application: ' . $is_application);
        return response()->json($is_application ? ($resource ?? ($message ?? '')) : [
            'success' => $statusCode,
            'status_code' => $statusCode,
            'message' => __($message),
            'data' => $resource,

        ], $statusCode);
    }
}
