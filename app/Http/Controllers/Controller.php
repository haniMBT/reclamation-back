<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function sendSuccessResponse($data, $code = 200): JsonResponse
    {
        return response()->json($data, $code);
    }
    public function sendErrorResponse($message = "error", $code = 400): JsonResponse
    {
        return response()->json($message, $code);
    }
}
