<?php

namespace Tests\Feature\Auth;

use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VaiTroQuyenSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'ten' => 'Test NguoiDung',
            'email' => 'test@ticket.it',
            'password' => '11111111',
            'password_confirmation' => '11111111',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('tong-quan', absolute: false));

        $this->assertTrue(auth()->user()->hasRole('requester'));
    }
}
