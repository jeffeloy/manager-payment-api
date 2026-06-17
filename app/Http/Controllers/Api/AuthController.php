<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'country' => $validated['country'],
            'currency' => $validated['currency'],
            'role' => UserRole::Employee,
        ]);

        $tokenResult = $user->createToken('auth-token');

        return response()->json([
            'message' => 'User registered successfully.',
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $tokenResult = $user->createToken('auth-token');

        return response()->json([
            'message' => 'Login successful.',
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->token();

        if ($token !== null) {
            $token->revoke();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make($request->user()),
        ]);
    }
}
