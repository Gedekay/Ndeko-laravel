<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendController extends Controller
{
    /**
     * Liste des amis.
     */
    public function index(Request $request)
    {
        $friends = Friendship::query()
            ->where('user_id', auth()->id())
            ->with([
                'friend:id,fullname,phone,profile_image,last_seen'
            ])
            ->latest()
            ->paginate(
                $request->get('per_page', 20)
            );

        return response()->json([
            'success' => true,
            'data' => $friends
        ]);
    }

    /**
     * Ajouter un ami via son numéro.
     */
    public function add(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'string'
            ]
        ]);

        $user = auth()->user();

        $friend = User::query()
            ->where('phone', $request->phone)
            ->first();

        if (!$friend) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.'
            ], 404);
        }

        if ($friend->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous ajouter vous-même.'
            ], 422);
        }

        if ($friend->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur est bloqué.'
            ], 403);
        }

        $alreadyFriend = Friendship::query()
            ->where('user_id', $user->id)
            ->where('friend_id', $friend->id)
            ->exists();

        if ($alreadyFriend) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur est déjà dans votre liste.'
            ], 409);
        }

        DB::transaction(function () use ($user, $friend) {

            Friendship::create([
                'user_id'   => $user->id,
                'friend_id' => $friend->id,
            ]);

            Friendship::create([
                'user_id'   => $friend->id,
                'friend_id' => $user->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ami ajouté avec succès.',
            'friend' => [
                'id' => $friend->id,
                'fullname' => $friend->fullname,
                'phone' => $friend->phone,
                'profile_image' => $friend->profile_image,
            ]
        ]);
    }

    /**
     * Supprimer un ami.
     */
    public function remove($friendId)
    {
        $userId = auth()->id();

        $deleted = DB::transaction(function () use (
            $userId,
            $friendId
        ) {

            Friendship::where([
                'user_id' => $userId,
                'friend_id' => $friendId
            ])->delete();

            Friendship::where([
                'user_id' => $friendId,
                'friend_id' => $userId
            ])->delete();

            return true;
        });

        return response()->json([
            'success' => $deleted,
            'message' => 'Ami supprimé avec succès.'
        ]);
    }

    /**
     * Vérifier si un utilisateur est ami.
     */
    public function check($friendId)
    {
        $isFriend = Friendship::query()
            ->where('user_id', auth()->id())
            ->where('friend_id', $friendId)
            ->exists();

        return response()->json([
            'success' => true,
            'is_friend' => $isFriend
        ]);
    }
}