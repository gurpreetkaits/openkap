<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;

class CommentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Comment);
    }

    /**
     * Get threaded video comments (only top-level with replies loaded).
     */
    public function getVideoComments(Video $video): Collection
    {
        return $video->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name,avatar_url', 'replies.user:id,name,avatar_url'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all commenters on a video (for mentions).
     */
    public function getVideoCommenters(Video $video): \Illuminate\Support\Collection
    {
        return $video->comments()
            ->whereNotNull('user_id')
            ->with('user:id,name,avatar_url')
            ->get()
            ->pluck('user')
            ->unique('id')
            ->values();
    }

    public function createComment(array $data): Comment
    {
        $comment = new Comment;
        $comment->video_id = $data['video_id'];
        $comment->content = $data['content'];
        $comment->timestamp_seconds = $data['timestamp_seconds'] ?? null;
        $comment->parent_id = $data['parent_id'] ?? null;
        $comment->mentions = $data['mentions'] ?? null;

        if (isset($data['user_id'])) {
            $comment->user_id = $data['user_id'];
        } else {
            $comment->author_name = $data['author_name'] ?? 'Anonymous';
        }

        $comment->save();
        $comment->load('user:id,name,avatar_url');

        return $comment;
    }

    public function updateComment(Comment $comment, array $data): Comment
    {
        $comment->content = $data['content'];
        $comment->mentions = $data['mentions'] ?? $comment->mentions;
        $comment->edited_at = now();
        $comment->save();

        return $comment;
    }

    public function findByVideoAndId(int $videoId, int $commentId): ?Comment
    {
        return Comment::where('video_id', $videoId)
            ->where('id', $commentId)
            ->with('user:id,name,avatar_url')
            ->first();
    }

    public function findById(int $commentId): ?Comment
    {
        return Comment::with('user:id,name,avatar_url')->find($commentId);
    }

    public function deleteComment(Comment $comment): bool
    {
        return $comment->delete();
    }
}
