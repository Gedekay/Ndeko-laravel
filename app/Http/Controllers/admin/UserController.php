<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {

                $query->where(function ($q) use ($request) {
                    $q->where('fullname', 'like', '%' . $request->search . '%')
                      ->orWhere('phone', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {

                if ($request->status === 'blocked') {
                    $query->where('is_blocked', true);
                }

                if ($request->status === 'active') {
                    $query->where('is_blocked', false);
                }
            })
            ->withCount([
                'messages',
                'conversations'
            ])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Voir un utilisateur
     */
    public function show($id)
    {
        $user = User::withCount([
                'messages',
                'conversations'
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Bloquer un utilisateur
     */
    public function block($id)
    {
        $user = User::findOrFail($id);

        if ($user->is_blocked) {
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur déjà bloqué'
            ]);
        }

        $user->update([
            'is_blocked' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur bloqué avec succès'
        ]);
    }

    /**
     * Débloquer un utilisateur
     */
    public function unblock($id)
    {
        $user = User::findOrFail($id);

        if (!$user->is_blocked) {
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur déjà actif'
            ]);
        }

        $user->update([
            'is_blocked' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur débloqué avec succès'
        ]);
    }

    /**
     * Supprimer un utilisateur (soft delete recommandé)
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous supprimer vous-même'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé'
        ]);
    }
}