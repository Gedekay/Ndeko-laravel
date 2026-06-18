<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Liste des conversations de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $conversations = $user
            ->conversations()
            ->with([
                'members:id,fullname,phone,profile_image,last_seen',
                'lastMessage'
            ])
            ->when($request->filled('search'), function ($query) use ($request) {

                $query->whereHas('members', function ($q) use ($request) {

                    $q->where('fullname', 'like', '%' . $request->search . '%')
                      ->orWhere('phone', 'like', '%' . $request->search . '%');
                });
            })
            ->latest('updated_at')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Afficher une conversation.
     */
    public function show($id)
    {
        $conversation = Conversation::with([
            'members:id,fullname,phone,profile_image,last_seen',
            'group'
        ])->findOrFail($id);

        $isMember = $conversation
            ->members()
            ->where('users.id', auth()->id())
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $conversation
        ]);
    }

    /**
     * Rechercher des utilisateurs
     * par fullname ou téléphone.
     */
    public function searchUsers(Request $request)
    {
        $request->validate([
            'search' => [
                'required',
                'string',
                'min:2'
            ]
        ]);

        $currentUserId = auth()->id();

        $users = User::query()
            ->where('id', '!=', $currentUserId)
            ->where('is_blocked', false)
            ->where(function ($query) use ($request) {

                $query->where(
                    'fullname',
                    'like',
                    '%' . $request->search . '%'
                )
                ->orWhere(
                    'phone',
                    'like',
                    '%' . $request->search . '%'
                );
            })
            ->select([
                'id',
                'fullname',
                'phone',
                'profile_image',
                'last_seen'
            ])
            ->limit(20)
            ->get()
            ->map(function ($user) use ($currentUserId) {

                $conversation = Conversation::query()
                    ->where('type', 'private')
                    ->whereHas('members', function ($q) use ($currentUserId) {
                        $q->where('users.id', $currentUserId);
                    })
                    ->whereHas('members', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    })
                    ->first();

                return [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'phone' => $user->phone,
                    'profile_image' => $user->profile_image,
                    'last_seen' => $user->last_seen,
                    'conversation_exists' => !is_null($conversation),
                    'conversation_id' => $conversation?->id
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'data' => $users
        ]);
    }

    /**
     * Créer ou ouvrir une conversation privée.
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'user_id' => [
                'required',
                'exists:users,id'
            ]
        ]);

        $currentUserId = auth()->id();

        if ($request->user_id == $currentUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Action impossible.'
            ], 422);
        }

        $targetUser = User::findOrFail(
            $request->user_id
        );

        if ($targetUser->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur bloqué.'
            ], 403);
        }

        $conversation = Conversation::query()
            ->where('type', 'private')
            ->whereHas('members', function ($q) use ($currentUserId) {
                $q->where('users.id', $currentUserId);
            })
            ->whereHas('members', function ($q) use ($targetUser) {
                $q->where('users.id', $targetUser->id);
            })
            ->first();

        if (!$conversation) {

            $conversation = DB::transaction(
                function () use (
                    $currentUserId,
                    $targetUser
                ) {

                    $conversation = Conversation::create([
                        'type' => 'private',
                        'created_by' => $currentUserId
                    ]);

                    $conversation->members()->attach([
                        $currentUserId,
                        $targetUser->id
                    ]);

                    return $conversation;
                }
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation prête.',
            'conversation_id' => $conversation->id
        ]);
    }

    /**
     * Quitter une conversation.
     */
    public function leave($id)
    {
        $conversation = Conversation::findOrFail($id);

        $conversation->members()
            ->detach(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Conversation quittée.'
        ]);
    }

    /**
     * Supprimer une conversation.
     */
    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);

        if (
            $conversation->type === 'group' &&
            $conversation->created_by !== auth()->id()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation supprimée.'
        ]);
    }
}
