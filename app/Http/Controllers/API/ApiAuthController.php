<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ApiAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'riscoin_id' => 'required|string|unique:users',
            'inviters_code' => 'required|string',
            'invested_amount' => 'required|numeric',
            'birth_date' => 'required|date',
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'riscoin_id' => $request->riscoin_id,
            'inviters_code' => $request->inviters_code,
            'invested_amount' => $request->invested_amount,
            'is_active' => true,
            'date_joined' => now(),
            'birth_date' => $request->birth_date,
            'phone_number' => $request->phone_number
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => true,
            'message'      => 'User registered successfully',
            'data'         => new UserResource($user),
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Track last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => true,
            'message'      => 'Login successful',
            'data'         => new UserResource($user->load(['team', 'managerLevel'])),
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Successfully logged out',
        ], 200);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        Password::sendResetLink(['email' => $request->email]);

        // Always return success to prevent email enumeration
        return response()->json([
            'status'  => true,
            'message' => 'If that email exists, a reset link has been sent.',
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'Profile data',
            'data'    => new UserResource($request->user()->load(['team', 'managerLevel'])),
        ], 200);
    }
}
