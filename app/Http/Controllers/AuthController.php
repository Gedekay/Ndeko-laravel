<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Inscription.
     */
    public function register(Request $request)
    {
        $request->validate([
            'fullname' => [
                'required',
                'string',
                'max:255'
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
                'unique:users,phone'
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)
            ],

            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ]
        ]);

        $data = [
            'fullname' => $request->fullname,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'last_seen' => now()
        ];

        if ($request->hasFile('profile_image')) {

            $data['profile_image'] = $request
                ->file('profile_image')
                ->store('profiles', 'public');
        }

        $user = User::create($data);

        $token = $user
            ->createToken('mobile')
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès.',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    /**
     * Connexion.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'string'
            ],
            'password' => [
                'required',
                'string'
            ]
        ]);

        $user = User::where(
            'phone',
            $request->phone
        )->first();

        if (
            !$user ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Téléphone ou mot de passe incorrect.'
            ], 401);
        }

        if ($user->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été bloqué.'
            ], 403);
        }

        $user->update([
            'last_seen' => now(),
            'is_online' => true
        ]);

        $token = $user
            ->createToken('mobile')
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Utilisateur connecté.
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }

    /**
     * Déconnexion.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        $user->update([
            'is_online' => false,
            'last_seen' => now()
        ]);

        $user->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.'
        ]);
    }

    /**
     * Déconnexion de tous les appareils.
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();

        $user->update([
            'is_online' => false,
            'last_seen' => now()
        ]);

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion sur tous les appareils.'
        ]);
    }

    /**
     * Rafraîchir le token.
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        $request->user()
            ->currentAccessToken()
            ?->delete();

        $token = $user
            ->createToken('mobile')
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token
        ]);
    }
}
