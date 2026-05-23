<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendSupportMessageRequest;
use App\Http\Resources\SupportConversationResource;
use App\Http\Resources\SupportMessageResource;
use App\Managers\SupportChatManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    public function __construct(protected SupportChatManager $manager) {}

    public function show(Request $request): JsonResponse
    {
        $conversation = $this->manager->getOrCreateConversationForUser($request->user());
        $messages = $this->manager->getMessagesForConversation($conversation);

        return response()->json([
            'data' => [
                'conversation' => new SupportConversationResource($conversation),
                'messages' => SupportMessageResource::collection($messages),
            ],
        ]);
    }

    public function store(SendSupportMessageRequest $request): JsonResponse
    {
        $message = $this->manager->sendMessageAsUser(
            $request->user(),
            $request->validated('body'),
        );

        return response()->json([
            'data' => new SupportMessageResource($message),
        ], 201);
    }

    public function markRead(Request $request): JsonResponse
    {
        $conversation = $this->manager->markConversationReadByUser($request->user());

        return response()->json([
            'data' => new SupportConversationResource($conversation),
        ]);
    }
}
