<?php

namespace App\Http\Controllers;

use App\Data\ChatSendData;
use App\Managers\ChatManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ChatManager $chatManager
    ) {}

    public function conversations(Request $request): JsonResponse
    {
        $conversations = $this->chatManager->getConversations($request->user());

        return response()->json(['conversations' => $conversations]);
    }

    public function messages(Request $request, int $id): JsonResponse
    {
        $result = $this->chatManager->getMessages($id, $request->user());

        if (! $result) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        return response()->json($result);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|integer',
        ]);

        $data = new ChatSendData(
            message: $validated['message'],
            conversationId: $validated['conversation_id'] ?? null,
        );

        try {
            $result = $this->chatManager->sendMessage($data, $request->user());

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function createConversation(Request $request): JsonResponse
    {
        $conversation = $this->chatManager->createConversation($request->user());

        return response()->json(['conversation' => $conversation], 201);
    }

    public function destroyConversation(Request $request, int $id): JsonResponse
    {
        $deleted = $this->chatManager->deleteConversation($id, $request->user());

        if (! $deleted) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        return response()->json(['message' => 'Conversation deleted.']);
    }
}
