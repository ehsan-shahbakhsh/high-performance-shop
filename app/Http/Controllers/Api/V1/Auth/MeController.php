<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Get the authenticated user.
     */
    public function __invoke(Request $request)
    {
        return ApiResponse::success(new AuthUserResource($request->user()));
    }
}
