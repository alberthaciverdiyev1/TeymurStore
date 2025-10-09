<?php


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('responseHelper')) {
    function responseHelper(string $message = null, int $statusCode = 200, $resource = null,$inline_request=false)
    {
        $is_application = $inline_request ? false : (bool) request()->query('is_application', false);
        $code = $is_application ? 200 : $statusCode;

        \Log::info('is_application: ' . $is_application);
        return response()->json($is_application ? ($resource ?? ($message ?? '')) : [
            'success' => $code,
            'status_code' => $code,
            'message' => __($message),
            'data' => $resource,

        ], $code);
    }
}
