<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Info(title="DSI Sales API", version="1.0.0", description="Tech-company sales platform — customers, products, sales, and reports.")
 *
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="API server")
 *
 * @OA\SecurityScheme(securityScheme="bearerAuth", type="http", scheme="bearer", bearerFormat="Sanctum")
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Issue a personal access token",
     *     tags={"Auth"},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"email","password"},
     *
     *         @OA\Property(property="email", type="string", example="admin@example.com"),
     *         @OA\Property(property="password", type="string", example="password"),
     *         @OA\Property(property="device_name", type="string", example="postman")
     *     )),
     *
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description="Invalid credentials")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['nullable', 'string'],
        ]);

        if (! Auth::validate(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $token = $user->createToken($data['device_name'] ?? 'api')->plainTextToken;

        return ApiResponse::ok([
            'token' => $token,
            'user' => (new UserResource($user->load('roles')))->resolve(),
        ], 'Authenticated');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Revoke the current token",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::ok(null, 'Logged out');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/me",
     *     summary="Current authenticated user",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::ok(new UserResource($request->user()->load('roles')));
    }
}
