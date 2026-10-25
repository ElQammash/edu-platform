<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_panels_default_to_english_and_ltr(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get('/teacher')
            ->assertOk()
            ->assertSee('dir="ltr"', false);
    }

    public function test_switching_to_arabic_saves_it_on_the_account_and_turns_the_panel_rtl(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->from('/teacher')
            ->get(route('panel.locale', 'ar'))
            ->assertRedirect('/teacher');

        $this->assertSame('ar', $teacher->refresh()->locale);

        $this->actingAs($teacher)
            ->get('/teacher')
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('panels.teacher.brand', [], 'ar'));
    }

    public function test_the_saved_language_follows_the_user_to_a_new_session(): void
    {
        $admin = User::factory()->admin()->create(['locale' => 'ar']);

        $this->actingAs($admin)->get('/admin')->assertSee('dir="rtl"', false);
    }

    public function test_guests_can_switch_on_the_login_page(): void
    {
        $this->get('/teacher/login')->assertSee('العربية');

        $this->from('/teacher/login')->get(route('panel.locale', 'ar'))->assertRedirect('/teacher/login');

        $this->get('/teacher/login')
            ->assertSee('dir="rtl"', false)
            ->assertSee('English');
    }

    public function test_unknown_languages_are_rejected(): void
    {
        $this->get(route('panel.locale', 'fr'))->assertNotFound();
    }
}
