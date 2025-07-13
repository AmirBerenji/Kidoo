<?php

if (!function_exists('apiResponse')) {
    function apiResponse($success, $message, $data = null, $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ], $status);
    }
}
