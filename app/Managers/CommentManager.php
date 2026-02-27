<?php

namespace App\Managers;

use App\Models\Comment;
use App\Models\User;
use App\Repositories\CommentRepository;
use App\Repositories\VideoRepository;

class CommentManager
{
    public function __construct(
        protected CommentRepository $comments,
        protected VideoRepository $videos,
        protected NotificationManager $notifications
    ) {}

    public function getVideoComments(int $videoId): array
    {
        $video = $this->videos->findOrFail($videoId);

        return $this->comments->getVideoComments($video)
            ->map(function ($comment) {
                return $this->formatCommentWithReplies($comment);
            })
            ->toArray();
    }

    public function getSharedVideoComments(string $token): ?array
    {
        $video = $this->videos->findByShareToken($token);

        if (! $video || ! $video->isShareLinkValid()) {
            return null;
        }

        return $this->getVideoComments($video->id);
    }

    /**
     * Get all users who have commented on a video (for @mentions).
     */
    public function getVideoCommenters(int $videoId): array
    {
        $video = $this->videos->findOrFail($videoId);

        return $this->comments->getVideoCommenters($video)
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ])
            ->toArray();
    }

    /**
     * Get commenters for shared video.
     */
    public function getSharedVideoCommenters(string $token): ?array
    {
        $video = $this->videos->findByShareToken($token);

        if (! $video || ! $video->isShareLinkValid()) {
            return null;
        }

        return $this->getVideoCommenters($video->id);
    }

    public function createComment(int $videoId, array $data, ?int $userId = null): Comment
    {
        $video = $this->videos->findOrFail($videoId);

        // Parse mentions from content
        $mentions = $this->parseMentions($data['content']);

        $commentData = [
            'video_id' => $video->id,
            'content' => $data['content'],
            'timestamp_seconds' => $data['timestamp_seconds'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'mentions' => $mentions,
        ];

        if ($userId) {
            $commentData['user_id'] = $userId;
        } else {
            $commentData['author_name'] = $data['author_name'] ?? 'Anonymous';
        }

        $comment = $this->comments->createComment($commentData);

        // Send notifications
        $this->notifyVideoOwner($video, $comment, $userId);
        $this->notifyMentionedUsers($comment, $mentions, $userId);
        $this->notifyParentCommentAuthor($comment, $userId);

        return $comment;
    }

    public function createSharedVideoComment(string $token, array $data, ?int $userId = null): ?Comment
    {
        $video = $this->videos->findByShareToken($token);

        if (! $video || ! $video->isShareLinkValid()) {
            return null;
        }

        return $this->createComment($video->id, $data, $userId);
    }

    /**
     * Update a comment (only content can be edited).
     */
    public function updateComment(int $commentId, array $data, int $userId): ?Comment
    {
        $comment = $this->comments->findById($commentId);

        if (! $comment || ! $comment->canEdit($userId)) {
            return null;
        }

        // Save old mentions before updating
        $oldMentions = $comment->mentions ?? [];

        // Parse new mentions
        $mentions = $this->parseMentions($data['content']);

        $updated = $this->comments->updateComment($comment, [
            'content' => $data['content'],
            'mentions' => $mentions,
        ]);

        // Notify newly mentioned users
        $newMentions = array_diff($mentions, $oldMentions);
        if (! empty($newMentions)) {
            $this->notifyMentionedUsers($updated, $newMentions, $userId);
        }

        return $updated;
    }

    public function deleteComment(int $videoId, int $commentId, ?int $userId = null): bool
    {
        // Require authenticated user for deletion
        if (! $userId) {
            return false;
        }

        $comment = $this->comments->findByVideoAndId($videoId, $commentId);

        if (! $comment) {
            return false;
        }

        $video = $this->videos->findOrFail($videoId);

        // Check if user can delete (own comment or video owner)
        if (! $comment->canDelete($userId, $video->user_id)) {
            return false;
        }

        return $this->comments->deleteComment($comment);
    }

    /**
     * Parse @mentions from content and return user IDs.
     */
    protected function parseMentions(string $content): array
    {
        preg_match_all('/@\[([^\]]+)\]\((\d+)\)/', $content, $matches);

        return array_map('intval', $matches[2] ?? []);
    }

    /**
     * Notify video owner about new comment.
     */
    protected function notifyVideoOwner($video, Comment $comment, ?int $userId): void
    {
        // Don't notify if commenter is the video owner
        if (! $userId || $video->user_id === $userId) {
            return;
        }

        $commenter = User::find($userId);
        if ($commenter) {
            $this->notifications->createCommentNotification($video, $commenter);
        }
    }

    /**
     * Notify mentioned users.
     */
    protected function notifyMentionedUsers(Comment $comment, array $mentionIds, ?int $authorId): void
    {
        if (empty($mentionIds)) {
            return;
        }

        $author = $authorId ? User::find($authorId) : null;
        $authorName = $author?->name ?? $comment->author_name ?? 'Someone';

        foreach ($mentionIds as $userId) {
            // Don't notify yourself
            if ($userId === $authorId) {
                continue;
            }

            $user = User::find($userId);
            if ($user) {
                $this->notifications->createMentionNotification($comment, $user, $authorName);
            }
        }
    }

    /**
     * Notify parent comment author about reply.
     */
    protected function notifyParentCommentAuthor(Comment $comment, ?int $userId): void
    {
        if (! $comment->parent_id) {
            return;
        }

        $parent = $this->comments->findById($comment->parent_id);
        if (! $parent || ! $parent->user_id) {
            return;
        }

        // Don't notify if replying to own comment
        if ($parent->user_id === $userId) {
            return;
        }

        $author = $userId ? User::find($userId) : null;
        $authorName = $author?->name ?? $comment->author_name ?? 'Someone';

        $parentAuthor = User::find($parent->user_id);
        if ($parentAuthor) {
            $this->notifications->createReplyNotification($comment, $parentAuthor, $authorName);
        }
    }

    public function formatComment(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'author_name' => $comment->author_display_name,
            'author_id' => $comment->user_id,
            'author_avatar' => $comment->user?->avatar_url,
            'timestamp_seconds' => $comment->timestamp_seconds,
            'parent_id' => $comment->parent_id,
            'mentions' => $comment->mentions,
            'is_edited' => $comment->isEdited(),
            'created_at' => $comment->created_at,
            'edited_at' => $comment->edited_at,
        ];
    }

    public function formatCommentWithReplies(Comment $comment): array
    {
        $formatted = $this->formatComment($comment);
        $formatted['replies'] = $comment->replies->map(fn ($reply) => $this->formatComment($reply))->toArray();
        $formatted['reply_count'] = count($formatted['replies']);

        return $formatted;
    }
}
