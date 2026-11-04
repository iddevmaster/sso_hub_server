<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'staff', 'customer'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        $this->admin = $this->makeUser('admin-user', 'admin');
        $this->staff = $this->makeUser('staff-user', 'staff');
    }

    private function makeUser(string $username, string $role): User
    {
        $user = User::forceCreate([
            'name' => $username,
            'username' => $username,
            'password' => bcrypt(str()->random(32)),
            'brn' => 'IDD0001',
            'agn' => 'AGN0001',
            'role' => $role,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function assertPassedMiddleware($response): void
    {
        $status = $response->getStatusCode();
        $this->assertNotEquals(401, $status);
        $this->assertNotEquals(403, $status);
        $this->assertFalse(
            in_array($status, [301, 302]) && in_array($response->headers->get('Location'), [url('/'), route('login')]),
            'Request was redirected away by auth middleware'
        );
    }

    public function test_register_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_guest_cannot_add_or_remove_course(): void
    {
        $this->post('/customers/add-course/1', ['courseSelect' => 1])->assertRedirect('/');
        $this->post('/customer/remove/1/1')->assertRedirect('/');
        // The old GET verb is gone.
        $this->get('/customer/remove/1/1')->assertStatus(405);
    }

    public function test_staff_can_add_and_remove_course(): void
    {
        $this->actingAs($this->staff);

        $this->assertPassedMiddleware($this->from('/customers')->post('/customers/add-course/999', ['courseSelect' => 1]));
        $this->assertPassedMiddleware($this->post('/customer/remove/999/1'));
    }

    public function test_staff_blocked_from_user_management(): void
    {
        $this->actingAs($this->staff);

        foreach (['/users/store', '/users/update', '/users/delete'] as $path) {
            $this->post($path, ['role' => 'admin'])->assertRedirect('/');
        }
    }

    public function test_staff_gets_403_on_non_course_data_routes(): void
    {
        $this->actingAs($this->staff);

        foreach (['agn', 'brn', 'role', 'perm', 'ctype'] as $type) {
            $this->post('/data/add', ['addType' => $type])->assertForbidden();
            $this->post('/data/update', ['addType' => $type])->assertForbidden();
            $this->post('/data/delete', ['deltype' => $type, 'delid' => 'admin'])->assertForbidden();
        }

        $this->assertTrue(Role::where('name', 'admin')->exists());
    }

    public function test_staff_still_passes_data_guard_for_courses(): void
    {
        $this->actingAs($this->staff);

        // Reaches the controller (business logic may fail with other errors, but not 401/403).
        $this->withoutExceptionHandling();
        try {
            $response = $this->post('/data/update', ['addType' => 'course', 'eid' => 999999]);
            $this->assertNotEquals(403, $response->getStatusCode());
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\Symfony\Component\HttpKernel\Exception\HttpException::class, $e);
        }
    }

    public function test_admin_passes_middleware_on_protected_routes(): void
    {
        $this->actingAs($this->admin);

        $this->withoutExceptionHandling();
        foreach (['/users/store', '/users/update', '/users/delete', '/data/add', '/data/update', '/data/delete'] as $path) {
            try {
                $response = $this->post($path, ['addType' => 'role', 'deltype' => 'none']);
                $this->assertPassedMiddleware($response);
            } catch (\Throwable $e) {
                // Controller-level errors (missing input) are fine; auth errors are not.
                $this->assertNotInstanceOf(\Symfony\Component\HttpKernel\Exception\HttpException::class, $e, $path);
            }
        }
    }
}
