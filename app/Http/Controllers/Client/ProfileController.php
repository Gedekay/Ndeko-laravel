<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Profil connecté.
     */
    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Mise à jour du profil.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'fullname' => [
                'required',
                'string',
                'max:255'
            ],
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ]
        ]);

        $data = [
            'fullname' => $request->fullname
        ];

        if ($request->hasFile('profile_image')) {

            if ($user->profile_image) {
                Storage::disk('public')
                    ->delete($user->profile_image);
            }

            $data['profile_image'] = $request
                ->file('profile_image')
                ->store('profiles', 'public');
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour.',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Supprimer la photo de profil.
     */
    public function removePhoto()
    {
        $user = auth()->user();

        if ($user->profile_image) {

            Storage::disk('public')
                ->delete($user->profile_image);

            $user->update([
                'profile_image' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo supprimée.'
        ]);
    }

    /**
     * Changer le mot de passe.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => [
                'required'
            ],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ]
        ]);

        $user = auth()->user();

        if (!Hash::check(
            $request->current_password,
            $user->password
        )) {

            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect.'
            ], 422);
        }

        $user->update([
            'password' => bcrypt(
                $request->new_password
            )
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié.'
        ]);
    }

    /**
     * Déconnexion.
     */
    public function logout()
    {
        auth()->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.'
        ]);
    }

    /**
     * Mettre à jour la dernière activité.
     */
    public function heartbeat()
    {
        $user = auth()->user();

        $user->update([
            'last_seen' => now()
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}