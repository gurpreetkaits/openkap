<?php

namespace App\Managers;

use App\Mail\FeedbackReceivedMail;
use App\Models\Feedback;
use App\Models\User;
use App\Repositories\FeedbackRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class FeedbackManager
{
    public function __construct(
        protected FeedbackRepository $feedbackRepository
    ) {}

    public function submitFeedback(User $user, string $title, string $description): Feedback
    {
        $feedback = $this->feedbackRepository->createFeedback([
            'user_id' => $user->id,
            'title' => $title,
            'description' => $description,
        ]);

        $this->sendAdminNotification($feedback, $user);

        return $feedback;
    }

    public function getUserFeedback(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->feedbackRepository->findByUserId($userId, $perPage);
    }

    protected function sendAdminNotification(Feedback $feedback, User $user): void
    {
        $adminEmail = config('mail.admin_email', config('mail.from.address'));

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new FeedbackReceivedMail($feedback, $user));
        }
    }
}
