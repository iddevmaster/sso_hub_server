<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_username_and_password(): void
    {
        $user = User::create([
            'name' => 'T', 'username' => '1100000000099', 'password' => bcrypt('secret-pass'),
            'role' => 'customer', 'brn' => '', 'agn' => '',
        ]);

        $this->post('/login', ['username' => '1100000000099', 'password' => 'secret-pass'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::create([
            'name' => 'T', 'username' => '1100000000099', 'password' => bcrypt('secret-pass'),
            'role' => 'customer', 'brn' => '', 'agn' => '',
        ]);

        $this->post('/login', ['username' => '1100000000099', 'password' => 'nope'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
