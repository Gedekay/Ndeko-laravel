<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Envoyer un message.
     */
    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => [
                'required',
                'exists:conversations,id'
            ],
            'type' => [
                'required',
                'in:text,image,video,audio,file'
            ],
            'content' => [
                'nullable',
                'string'
            ],
            'file' => [
                'nullable',
                'file',
                'max:51200' // 50MB
            ]
        ]);

        $conversation = Conversation::findOrFail(
            $request->conversation_id
        );

        $isMember = $conversation
            ->members()
            ->where('user_id', auth()->id())
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        $messageData = [
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'type' => $request->type,
            'content' => $request->content,
        ];

        if ($request->hasFile('file')) {

            $file = $request->file('file');

            $path = $file->store(
                'messages',
                'public'
            );

            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['file_path'] = $path;
            $messageData['mime_type'] = $file->getMimeType();
            $messageData['file_size'] = $file->getSize();
        }

        $message = Message::create($messageData);

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé.',
            'data' => $message->load('sender')
        ], 201);
    }

    /**
     * Liste des messages.
     */
    public function index(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail(
            $conversationId
        );

        $isMember = $conversation
            ->members()
            ->where('user_id', auth()->id())
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        $messages = Message::query()
            ->where(
                'conversation_id',
                $conversationId
            )
            ->with([
                'sender:id,fullname,profile_image'
            ])
            ->orderBy('created_at')
            ->paginate(
                $request->get('per_page', 50)
            );

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Afficher un message.
     */
    public function show($id)
    {
        $message = Message::with([
            'sender:id,fullname,profile_image'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Modifier un message.
     */
    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string'
        ]);

        $message->update([
            'content' => $request->content,
            'edited_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message modifié.',
            'data' => $message
        ]);
    }

    /**
     * Supprimer un message.
     */
    public function destroy($id)
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        if ($message->file_path) {
            Storage::disk('public')
                ->delete($message->file_path);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message supprimé.'
        ]);
    }

    /**
     * Télécharger un fichier.
     */
    public function download($id)
    {
        $message = Message::findOrFail($id);

        if (!$message->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun fichier.'
            ], 404);
        }

        return Storage::disk('public')
            ->download(
                $message->file_path,
                $message->file_name
            );
    }
}