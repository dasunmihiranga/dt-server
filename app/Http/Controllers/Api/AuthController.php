<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'balance' => 0,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            activity()
                ->causedBy($user)
                ->log('User registered');

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'balance' => (float) $user->balance,
                ],
                'token' => $token,
            ], 'Registration successful', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->errorResponse('Invalid credentials', null, 401);
            }

            $user = User::where('email', $request->email)->first();
            $token = $user->createToken('auth-token')->plainTextToken;

            activity()
                ->causedBy($user)
                ->log('User logged in');

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'balance' => (float) $user->balance,
                ],
                'token' => $token,
            ], 'Login successful');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        activity()
            ->causedBy($user)
            ->log('User logged out');

        return $this->successResponse(null, 'Logout successful');
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => (float) $user->balance,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }
}
