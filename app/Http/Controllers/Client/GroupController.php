<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string'
            ]
        ]);

        $group = DB::transaction(function () use ($request) {

            $conversation = Conversation::create([
                'type' => 'group',
                'created_by' => auth()->id()
            ]);

            $conversation->members()->attach(auth()->id());

            return Group::create([
                'conversation_id' => $conversation->id,
                'name' => $request->name,
                'description' => $request->description,
                'owner_id' => auth()->id()
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Groupe créé avec succès.',
            'data' => $group
        ], 201);
    }

    public function addMember(Request $request, $groupId)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $group = Group::with('conversation')
            ->findOrFail($groupId);

        if ($group->owner_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut ajouter des membres.'
            ], 403);
        }

        $user = User::where(
            'phone',
            $request->phone
        )->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.'
            ], 404);
        }

        if ($user->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur bloqué.'
            ], 403);
        }

        $exists = $group->conversation
            ->members()
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur est déjà membre.'
            ], 409);
        }

        $group->conversation
            ->members()
            ->attach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Membre ajouté avec succès.'
        ]);
    }

    public function removeMember($groupId, $userId)
    {
        $group = Group::with('conversation')
            ->findOrFail($groupId);

        if ($group->owner_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut retirer des membres.'
            ], 403);
        }

        if ($group->owner_id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Le propriétaire ne peut pas être retiré.'
            ], 422);
        }

        $exists = $group->conversation
            ->members()
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non membre du groupe.'
            ], 404);
        }

        $group->conversation
            ->members()
            ->detach($userId);

        return response()->json([
            'success' => true,
            'message' => 'Membre retiré avec succès.'
        ]);
    }

    public function leave($groupId)
    {
        $group = Group::with('conversation')
            ->findOrFail($groupId);

        if ($group->owner_id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Le propriétaire doit transférer le groupe ou le supprimer.'
            ], 422);
        }

        $group->conversation
            ->members()
            ->detach(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Vous avez quitté le groupe.'
        ]);
    }

    public function members(Request $request, $groupId)
    {
        $group = Group::with([
            'conversation.members' => function ($query) {
                $query->select(
                    'users.id',
                    'fullname',
                    'phone',
                    'profile_image',
                    'last_seen'
                );
            }
        ])->findOrFail($groupId);

        return response()->json([
            'success' => true,
            'count' => $group->conversation->members->count(),
            'members' => $group->conversation->members
        ]);
    }

    public function show($groupId)
    {
        $group = Group::with([
            'owner:id,fullname,phone,profile_image',
            'conversation.members:id,fullname,phone,profile_image'
        ])->findOrFail($groupId);

        return response()->json([
            'success' => true,
            'data' => $group
        ]);
    }

    public function update(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);

        if ($group->owner_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string'
        ]);

        $group->update([
            'name' => $request->name ?? $group->name,
            'description' => $request->description ?? $group->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Groupe mis à jour.',
            'data' => $group
        ]);
    }

    public function destroy($groupId)
    {
        $group = Group::findOrFail($groupId);

        if ($group->owner_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        DB::transaction(function () use ($group) {

            $group->conversation()->delete();

            $group->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Groupe supprimé.'
        ]);
    }
}