<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_a_user_gets_their_saved_interface_language(): void
    {
        $user = User::factory()->create();
        $user->settings->update(['ui_language_code' => 'en']);

        $this->actingAs($user)
            ->get('/settings', ['Accept-Language' => 'uk'])
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'en'));
    }

    public function test_a_guest_gets_the_browser_language_when_it_is_active(): void
    {
        $this->get('/login', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'en'))
            ->assertSee('<html lang="en">', false);
    }

    public function test_a_guest_gets_the_default_for_an_unsupported_or_inactive_language(): void
    {
        $this->get('/login', ['Accept-Language' => 'de'])
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'uk'));

        DB::table('ui_languages')->where('code', 'en')->update(['is_active' => false]);

        $this->get('/login', ['Accept-Language' => 'en'])
            ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'uk'));
    }

    public function test_server_side_messages_follow_the_locale(): void
    {
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'], ['Accept-Language' => 'uk'])
            ->assertSessionHasErrors(['email' => __('auth.failed', locale: 'uk')]);
    }

    public function test_validation_messages_use_translated_field_names_and_values(): void
    {
        $this->post('/register', ['name' => '', 'date_of_birth' => now()->addDay()->toDateString()], ['Accept-Language' => 'uk'])
            ->assertSessionHasErrors([
                'name' => 'Поле ім’я обов’язкове.',
                'date_of_birth' => 'Поле дата народження має бути датою до сьогодні.',
            ]);
    }

    public function test_a_group_name_is_not_called_the_users_name(): void
    {
        $user = User::factory()->create();
        $user->settings->update(['ui_language_code' => 'uk']);

        $this->actingAs($user)->post('/groups', ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Поле назва групи обов’язкове.']);
    }

    public function test_custom_validation_messages_are_translated(): void
    {
        $user = User::factory()->create(['date_of_birth' => now()->subYears(16)]);
        $user->settings->update(['ui_language_code' => 'uk']);

        $this->actingAs($user)->post('/groups', ['slug' => 'Retro Gaming'])
            ->assertSessionHasErrors(['slug' => __('validation.custom.slug.regex', locale: 'uk')]);

        $this->actingAs($user)->patch('/settings', ['show_adult_content' => true])
            ->assertSessionHasErrors(['show_adult_content' => __('validation.custom.show_adult_content.adult_only', locale: 'uk')]);
    }
}
