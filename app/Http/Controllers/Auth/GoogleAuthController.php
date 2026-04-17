<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find or create user
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            $isNewUser = false;

            if ($user) {
                // Update existing user
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar_url' => $googleUser->avatar,
                ]);
            } else {
                // Create new user
                $isNewUser = true;
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar_url' => $googleUser->avatar,
                    'username' => User::generateUniqueUsername($googleUser->email),
                    'password' => null, // No password for OAuth users
                    'email_verified_at' => now(),
                ]);
            }

            // Also treat existing users who haven't completed onboarding as new
            if (! $isNewUser && is_null($user->onboarded_at)) {
                $isNewUser = true;
            }

            // Create auth token
            $token = $user->createToken('google-auth')->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = config('services.frontend.url');

            $userData = urlencode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url ?? $user->avatar,
                'plan_type' => $user->getPlanType(),
                'max_recording_seconds' => $user->getMaxRecordingSeconds(),
                'min_recording_seconds' => $user->getMinRecordingSeconds(),
                'is_admin' => $user->isAdmin(),
                'is_new_user' => $isNewUser,
            ]));

            // Use URL fragment (#) instead of query string (?) to prevent token leaking
            // via server logs, Referer headers, and proxy caches
            return redirect("$frontendUrl/auth/callback#token=$token&user=$userData");

        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: '.$e->getMessage());
            $frontendUrl = config('services.frontend.url');

            return redirect("$frontendUrl/login?error=authentication_failed");
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'bio' => $user->bio,
                'avatar' => $user->avatar_url ?? $user->avatar,
                'website' => $user->website,
                'location' => $user->location,
                'created_at' => $user->created_at->toISOString(),
                'plan_type' => $user->getPlanType(),
                'max_recording_seconds' => $user->getMaxRecordingSeconds(),
                'min_recording_seconds' => $user->getMinRecordingSeconds(),
                'can_record' => $user->canRecordVideo(),
                'videos_count' => $user->getVideosCount(),
                'max_videos' => $user->getMaxVideos(),
                'is_admin' => $user->isAdmin(),
            ],
        ]);
    }
}
