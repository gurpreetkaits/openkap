<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Google sign-in was failing in production with
 * "SQLSTATE[22001]: Data too long for column 'avatar_url'" — the column was
 * varchar(255) and Google hands back longer URLs. Users could not sign up.
 */
class GoogleAvatarUrlTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_avatar_url_longer_than_the_old_column_limit_is_stored(): void
    {
        // Shaped like a real Google avatar URL, past the old 255 limit.
        $longUrl = 'https://lh3.googleusercontent.com/a/'.str_repeat('ACg8ocK9xQ2mN7vB', 20).'=s96-c';

        $this->assertGreaterThan(255, strlen($longUrl));

        $user = User::factory()->create(['avatar_url' => $longUrl]);

        $this->assertSame($longUrl, $user->fresh()->avatar_url);
    }

    #[Test]
    public function an_avatar_url_at_the_column_limit_is_stored(): void
    {
        $url = 'https://lh3.googleusercontent.com/a/'.str_repeat('x', 2048 - 36);

        $this->assertSame(2048, strlen($url));

        $user = User::factory()->create(['avatar_url' => $url]);

        $this->assertSame($url, $user->fresh()->avatar_url);
    }

    #[Test]
    public function an_absurd_avatar_url_costs_the_avatar_not_the_sign_in(): void
    {
        $controller = new \App\Http\Controllers\Auth\GoogleAuthController;

        $method = new \ReflectionMethod($controller, 'normaliseAvatarUrl');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($controller, str_repeat('x', 5000)));
        $this->assertNull($method->invoke($controller, null));
        $this->assertNull($method->invoke($controller, ''));
        $this->assertSame('https://example.com/a.png', $method->invoke($controller, 'https://example.com/a.png'));
    }
}
