<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Liste des conversations.
     */
    public function index(Request $request)
    {
        $conversations = Conversation::query()
            ->with([
                'members:id,fullname,phone,profile_image',
                'lastMessage'
            ])
            ->withCount([
                'members',
                'messages'
            ])
            ->when(
                $request->filled('type'),
                fn ($query) =>
                $query->where(
                    'type',
                    $request->type
                )
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {

                    $query->whereHas(
                        'members',
                        function ($q) use ($request) {

                            $q->where(
                                'fullname',
                                'like',
                                '%' . $request->search . '%'
                            )
                            ->orWhere(
                                'phone',
                                'like',
                                '%' . $request->search . '%'
                            );
                        }
                    );
                }
            )
            ->latest()
            ->paginate(
                $request->integer(
                    'per_page',
                    20
                )
            );

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Détails d'une conversation.
     */
    public function show($id)
    {
        $conversation = Conversation::with([
            'members:id,fullname,phone,profile_image,last_seen',
            'group',
            'lastMessage'
        ])
        ->withCount([
            'members',
            'messages'
        ])
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $conversation
        ]);
    }

    /**
     * Messages d'une conversation.
     */
    public function messages(
        Request $request,
        $conversationId
    ) {

        $conversation = Conversation::findOrFail(
            $conversationId
        );

        $messages = $conversation
            ->messages()
            ->with([
                'sender:id,fullname,phone,profile_image'
            ])
            ->latest()
            ->paginate(
                $request->integer(
                    'per_page',
                    50
                )
            );

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Membres d'une conversation.
     */
    public function members($conversationId)
    {
        $conversation = Conversation::with([
            'members:id,fullname,phone,profile_image,last_seen'
        ])->findOrFail($conversationId);

        return response()->json([
            'success' => true,
            'count' => $conversation->members->count(),
            'data' => $conversation->members
        ]);
    }

    /**
     * Statistiques.
     */
    public function statistics($conversationId)
    {
        $conversation = Conversation::withCount([
            'members',
            'messages'
        ])->findOrFail($conversationId);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation_id' => $conversation->id,
                'type' => $conversation->type,
                'members_count' => $conversation->members_count,
                'messages_count' => $conversation->messages_count,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at
            ]
        ]);
    }

    /**
     * Supprimer une conversation.
     */
    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation supprimée.'
        ]);
    }
}
