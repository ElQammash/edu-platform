<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Requests from the SPA origin get Sanctum's session (stateful) middleware.
        $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mona Adel',
            'email' => 'mona@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'grade_level' => 'Grade 11',
            'parent_phone' => '01012345678',
        ], $overrides);
    }

    public function test_student_can_register(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), $this->registrationData());

        $response->assertCreated()
            ->assertJsonPath('data.email', 'mona@example.com')
            ->assertJsonPath('data.roles', [Role::Student->value])
            ->assertJsonPath('data.student_profile.parent_phone', '01012345678')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'mona@example.com')->firstOrFail();
        $this->assertTrue($user->isStudent());
        $this->assertNotNull($user->studentProfile);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_registration_cannot_choose_a_role(): void
    {
        $this->postJson(route('api.v1.auth.register'), $this->registrationData(['role' => 'admin']))
            ->assertCreated()
            ->assertJsonPath('data.roles', [Role::Student->value]);
    }

    public function test_registration_requires_valid_data(): void
    {
        User::factory()->student()->create(['email' => 'taken@example.com']);

        $this->postJson(route('api.v1.auth.register'), $this->registrationData([
            'name' => '',
            'email' => 'taken@example.com',
            'password_confirmation' => 'something-else',
            'parent_phone' => '12345',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'parent_phone']);

        $this->assertGuest('web');
    }

    public function test_validation_errors_are_translated_to_arabic(): void
    {
        $this->withHeader('Accept-Language', 'ar-EG,ar;q=0.9,en;q=0.8')
            ->postJson(route('api.v1.auth.register'), $this->registrationData(['name' => '']))
            ->assertUnprocessable()
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonValidationErrors(['name' => 'حقل الاسم مطلوب.']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->student()->create(['password' => 'secret-password']);

        $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles', [Role::Student->value]);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_teacher_login_returns_teacher_profile(): void
    {
        $teacher = User::factory()->teacher()->create(['password' => 'secret-password']);

        $this->postJson(route('api.v1.auth.login'), [
            'email' => $teacher->email,
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('data.roles', [Role::Teacher->value])
            ->assertJsonPath('data.teacher_profile.is_approved', true);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->student()->create(['password' => 'secret-password']);

        $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => __('auth.failed')]);

        $this->assertGuest('web');
    }

    public function test_login_is_throttled(): void
    {
        $user = User::factory()->student()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('api.v1.auth.login'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->postJson(route('api.v1.auth.login'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user, 'web')
            ->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.student_profile.grade_level', $user->studentProfile?->grade_level);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson(route('api.v1.auth.me'))->assertUnauthorized();
    }

    public function test_the_interface_language_is_saved_on_the_account(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->putJson(route('api.v1.me.locale'), ['locale' => 'en'])->assertNoContent();
        $this->assertSame('en', $user->refresh()->locale);
        $this->assertSame('en', $user->preferredLocale());

        $this->actingAs($user)->getJson(route('api.v1.auth.me'))->assertJsonPath('data.locale', 'en');
        $this->actingAs($user)->putJson(route('api.v1.me.locale'), ['locale' => 'fr'])->assertUnprocessable();
    }

    public function test_guests_get_a_401_even_without_asking_for_json(): void
    {
        // e.g. a link opened in a new tab: no Accept: application/json header.
        $this->withHeaders(['Accept' => 'text/html'])
            ->get(route('api.v1.auth.me'))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->student()->create(['password' => 'secret-password']);

        $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertOk();

        $this->postJson(route('api.v1.auth.logout'))->assertNoContent();

        $this->assertGuest('web');
    }
}
