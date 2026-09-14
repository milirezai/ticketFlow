<?php

namespace Tests\Feature;

use App\Models\Service\Otp\Otp;
use App\Models\User\User;
use App\Notifications\Service\Otp\SendOtpNotification;
use App\Services\Otp\OtpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPassword(string $password): User
    {
        return User::factory()->create(['password' => Hash::make($password)]);
    }

       public function test_change_password_updates_password_and_rotates_token(): void
    {
        $user = $this->userWithPassword('12345678');
        Sanctum::actingAs($user);
        $this->postJson(route('password.change'), [
            'old_password' => '12345678',
            'password' => '87654321',
            'password_confirmation' => '87654321',
        ])->assertStatus(200)
            ->assertJsonStructure(['data', 'token']);
        $this->assertTrue(Hash::check('87654321', $user->refresh()->password));
        $this->assertCount(1, $user->tokens);
    }

    public function test_change_password_rejects_wrong_old_password(): void
    {
        $user = $this->userWithPassword('12345678');
        Sanctum::actingAs($user);
        $this->postJson(route('password.change'), [
            'old_password' => '99999999',
            'password' => '87654321',
            'password_confirmation' => '87654321',
        ])->assertStatus(401);
        $this->assertTrue(Hash::check('12345678', $user->refresh()->password));
    }

    public function test_change_password_requires_confirmation(): void
    {
        $user = $this->userWithPassword('12345678');
        Sanctum::actingAs($user);
        $this->postJson(route('password.change'), [
            'old_password' => '12345678',
            'password' => '87654321',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_forgot_password_sends_otp_notification_and_creates_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'test1@example.com']);
        $this->postJson(route('password.forgot'), ['email' => 'test1@example.com'])
            ->assertStatus(200);
        Notification::assertSentTo($user, SendOtpNotification::class);
        $otp = Otp::where('user_id', $user->id)->first();
        $this->assertNotNull($otp);
        $this->assertFalse($otp->is_used);
        $this->assertTrue(Hash::isHashed($otp->code));
        $this->assertTrue($otp->expired_at->greaterThan(now()->addMinute()));
        $this->assertTrue($otp->expired_at->lessThan(now()->addMinutes(3)));
    }

    public function test_forgot_password_rejects_unknown_email(): void
    {
        $this->postJson(route('password.forgot'), ['email' => 'test2@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_otp_mail_contains_subject_and_code(): void
    {
        $user = User::factory()->create(['first_name' => 'test', 'last_name' => 'something']);
        $mail = (new SendOtpNotification('123456'))->toMail($user);
        $this->assertSame('TicketFlow OTP verification', $mail->subject);
        $this->assertStringContainsString('123456', $mail->render());
    }

    public function test_reset_password_with_valid_otp_updates_password(): void
    {
        $user = User::factory()->create(['email' => 'test3@example.com']);
        $code = app(OtpService::class)->setTtl(5)->generate($user);
        $this->postJson(route('password.reset'), [
            'email' => 'test3@example.com',
            'otp' => $code,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertStatus(200)
            ->assertJsonStructure(['data', 'token']);
        $this->assertTrue(Hash::check('newpassword1', $user->refresh()->password));
        $this->assertDatabaseHas('otps', ['id' => Otp::first()->id, 'is_used' => 1]);
    }

    public function test_reset_password_rejects_invalid_otp(): void
    {
        $user = User::factory()->create(['email' => 'test4@example.com']);
        $this->postJson(route('password.reset'), [
            'email' => 'test4@example.com',
            'otp' => 999999,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertStatus(404);
    }

    public function test_reset_password_rejects_already_used_otp(): void
    {
        $user = User::factory()->create(['email' => 'test5@example.com']);
        $code = app(OtpService::class)->setTtl(5)->generate($user);
        app(OtpService::class)->verify($user, $code);
        $this->postJson(route('password.reset'), [
            'email' => 'test5@example.com',
            'otp' => $code,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertStatus(404);
    }

    public function test_reset_password_rejects_expired_otp(): void
    {
        $user = User::factory()->create(['email' => 'test6@example.com']);
        $code = app(OtpService::class)->setTtl(1)->generate($user);
        Carbon::setTestNow(now()->addMinutes(2));
        $this->postJson(route('password.reset'), [
            'email' => 'test6@example.com',
            'otp' => $code,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertStatus(404);
        Carbon::setTestNow();
    }

    public function test_otp_service_generates_hashed_code_with_ttl(): void
    {
        $user = User::factory()->create();
        $code = app(OtpService::class)->setTtl(3)->generate($user);
        $otp = Otp::where('user_id', $user->id)->first();
        $this->assertNotNull($otp);
        $this->assertNotSame((string) $code, $otp->code);
        $this->assertTrue(Hash::check((string) $code, $otp->code));
        $this->assertFalse($otp->is_used);
    }

    public function test_otp_service_verify_marks_otp_used_once(): void
    {
        $user = User::factory()->create();
        $service = app(OtpService::class);
        $code = $service->setTtl(5)->generate($user);
        $this->assertTrue($service->verify($user, (string) $code)['success']);
        $this->assertFalse($service->verify($user, (string) $code)['success']);
        $this->assertTrue(Otp::first()->is_used);
    }

    public function test_otp_service_verify_rejects_expired_otp(): void
    {
        $user = User::factory()->create();
        $service = app(OtpService::class);
        $code = $service->setTtl(1)->generate($user);
        Carbon::setTestNow(now()->addMinutes(2));
        $result = $service->verify($user, (string) $code);
        Carbon::setTestNow();
        $this->assertFalse($result['success']);
        $this->assertSame('OTP has expired.', $result['message']);
    }
}
