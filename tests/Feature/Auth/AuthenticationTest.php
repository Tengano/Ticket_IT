<?php

namespace Tests\Feature\Auth;

use App\Models\NguoiDung;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = NguoiDung::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '11111111',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('tong-quan', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = NguoiDung::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = NguoiDung::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = NguoiDung::factory()->inactive()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => '11111111',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_users_are_logged_out_of_active_sessions(): void
    {
        $user = NguoiDung::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/tong-quan');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_all_four_roles_can_login(): void
    {
        $this->seed(VaiTroQuyenSeeder::class);

        foreach (['requester', 'agent', 'lead', 'admin'] as $role) {
            $user = NguoiDung::factory()->create();
            $user->assignRole($role);

            $this->post('/login', [
                'email' => $user->email,
                'password' => '11111111',
            ])->assertRedirect(route('tong-quan', absolute: false));

            $this->assertAuthenticatedAs($user);

            $this->post('/logout');
            $this->assertGuest();
        }
    }
}
