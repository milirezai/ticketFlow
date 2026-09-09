<?php

namespace Tests\Feature;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson(route('register'), [
            'first_name' => 'test',
            'last_name' => 'test_last_name',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'mobile' => '09123456789',
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonStructure([
                'data' => ['first_name', 'last_name', 'mobile', 'email', 'profile_photo_path'],
                'token',
            ]);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'activation' => 0,
            'status' => 1,
        ]);
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertCount(1, $user->tokens);
    }

     public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);
        $this->postJson(route('register'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_invalid_email(): void
    {
        $this->postJson(route('register'), [
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson(route('register'), [
            'email' => 'test1@example.com',
            'password' => 'password123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_password_shorter_than_8_characters(): void
    {
        $this->postJson(route('register'), [
            'email' => 'test2@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_mobile_not_11_digits(): void
    {
        $this->postJson(route('register'), [
            'email' => 'test3@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'mobile' => '123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['mobile']);
    }

    public function test_register_rejects_invalid_profile_photo_extension(): void
    {
        $this->postJson(route('register'), [
            'email' => 'test4@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'profile_photo_path' => UploadedFile::fake()->create('test.txt', 100),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['profile_photo_path']);
    }

    public function test_login_returns_user_and_token(): void
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'email' => 'test5@example.com',
            'password' => Hash::make($password),
        ]);
        $this->postJson(route('login'), [
            'email' => 'test5@example.com',
            'password' => $password,
        ])->assertStatus(200)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure(['data', 'token']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'test6@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $this->postJson(route('login'), [
            'email' => 'test6@example.com',
            'password' => 'wrongpass',
        ])->assertStatus(401);
    }

    public function test_login_deletes_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'test7@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $user->createToken('old-1');
        $user->createToken('old-2');
        $this->assertCount(2, $user->tokens);
        $this->postJson(route('login'), [
            'email' => 'test7@example.com',
            'password' => 'secret123',
        ])->assertStatus(200);
        $this->assertCount(1, $user->refresh()->tokens);
    }

    public function test_login_rejects_unregistered_email(): void
    {
        $this->postJson(route('login'), [
            'email' => 'test8@example.com',
            'password' => 'secret123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson(route('logout'))->assertNoContent();
        $this->assertCount(0, $user->refresh()->tokens);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson(route('logout'))->assertStatus(401);
    }
}
