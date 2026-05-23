<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendSupportMessageRequest;
use App\Http\Resources\SupportConversationResource;
use App\Http\Resources\SupportMessageResource;
use App\Managers\SupportChatManager;
use App\Models\SupportConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupportAdminController extends Controller
{
    public function __construct(protected SupportChatManager $manager) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->input('search');

        $paginated = $this->manager->listConversationsForAdmin($perPage, $search);

        return SupportConversationResource::collection($paginated);
    }

    public function show(SupportConversation $conversation): JsonResponse
    {
        $conversation->load(['user', 'latestMessage']);
        $messages = $this->manager->getMessagesForConversation($conversation);

        return response()->json([
            'data' => [
                'conversation' => new SupportConversationResource($conversation),
                'messages' => SupportMessageResource::collection($messages),
            ],
        ]);
    }

    public function reply(SendSupportMessageRequest $request, SupportConversation $conversation): JsonResponse
    {
        $message = $this->manager->sendMessageAsAdmin(
            $request->user(),
            $conversation,
            $request->validated('body'),
        );

        return response()->json([
            'data' => new SupportMessageResource($message),
        ], 201);
    }

    public function markRead(SupportConversation $conversation): JsonResponse
    {
        $updated = $this->manager->markConversationReadByAdmin($conversation);
        $updated->load(['user', 'latestMessage']);

        return response()->json([
            'data' => new SupportConversationResource($updated),
        ]);
    }
}
