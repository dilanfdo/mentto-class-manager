<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Features;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'The email or password is wrong.']);
        }

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            ...$this->profile($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    private function profile(User $user): array
    {
        $tenant = $user->tenant?->load('features');

        return [
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'tenant' => $tenant?->only(['id', 'name', 'slug', 'type', 'status']),
            'features' => $tenant?->enabledFeatures() ?? [],
            'feature_names' => array_map(fn ($f) => $f['name'], Features::all()),
        ];
    }
}
