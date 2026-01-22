<?php

namespace App\Http\Controllers;

use App\Managers\CommentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(
        protected CommentManager $commentManager
    ) {}

    public function index($videoId)
    {
        $comments = $this->commentManager->getVideoComments($videoId);

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function store(Request $request, $videoId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
            'timestamp_seconds' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        // For direct video ID access, user must own the video
        // (shared video comments go through storeByToken which validates the share link)
        $video = \App\Models\Video::find($videoId);
        if (! $video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment = $this->commentManager->createComment(
            $videoId,
            $validated,
            Auth::id()
        );

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $this->commentManager->formatComment($comment),
        ], 201);
    }

    public function update(Request $request, $videoId, $commentId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $comment = $this->commentManager->updateComment($commentId, $validated, $userId);

        if (! $comment) {
            return response()->json(['message' => 'Comment not found or unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $this->commentManager->formatComment($comment),
        ]);
    }

    public function commenters($videoId)
    {
        $commenters = $this->commentManager->getVideoCommenters($videoId);

        return response()->json([
            'commenters' => $commenters,
        ]);
    }

    public function destroy($videoId, $commentId)
    {
        $userId = Auth::id();
        $deleted = $this->commentManager->deleteComment($videoId, $commentId, $userId);

        if (! $deleted) {
            return response()->json(['message' => 'Comment not found or unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }

    public function indexByToken($token)
    {
        $comments = $this->commentManager->getSharedVideoComments($token);

        if ($comments === null) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function storeByToken(Request $request, $token)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
            'timestamp_seconds' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $comment = $this->commentManager->createSharedVideoComment(
            $token,
            $validated,
            Auth::id()
        );

        if ($comment === null) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $this->commentManager->formatComment($comment),
        ], 201);
    }

    public function commentersByToken($token)
    {
        $commenters = $this->commentManager->getSharedVideoCommenters($token);

        if ($commenters === null) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        return response()->json([
            'commenters' => $commenters,
        ]);
    }
}
