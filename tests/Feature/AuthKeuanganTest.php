<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthKeuanganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run specific seeder to ensure roles exist
        $this->seed(RoleSeeder::class);
    }

    public function test_staff_keuangan_can_access()
    {
        $email = 'staff@example.com';
        $user = User::factory()->create([
            'email' => $email,
            'name' => 'Staff Name'
        ]);
        $user->assignRole('Staff Keuangan');

        echo "\nTest Staff: User roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
        echo "Test Staff: check 'Staff Keuangan': " . ($user->hasRole('Staff Keuangan') ? 'true' : 'false') . "\n";

        $response = $this->postJson('/api/keuangan/auth/check', [
            'google_token' => $email
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Staff Keuangan found']);
    }

    public function test_wali_murid_cannot_access()
    {
        $email = 'wali@example.com';
        $user = User::factory()->create([
            'email' => $email,
            'name' => 'Wali Name'
        ]);
        $user->assignRole('Wali Murid');

        echo "\nTest Wali: User roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
        echo "Test Wali: check 'Staff Keuangan': " . ($user->hasRole('Staff Keuangan') ? 'true' : 'false') . "\n";

        $response = $this->postJson('/api/keuangan/auth/check', [
            'google_token' => $email
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized. You are not Staff Keuangan.']);
    }

    public function test_superadmin_access_behavior()
    {
        $email = 'admin@example.com';
        $user = User::factory()->create([
            'email' => $email,
            'name' => 'Super Admin'
        ]);
        $user->assignRole('superadmin'); // Lowercase as per seeder

        echo "\nTest Admin: User roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
        echo "Test Admin: check 'Staff Keuangan': " . ($user->hasRole('Staff Keuangan') ? 'true' : 'false') . "\n";

        $response = $this->postJson('/api/keuangan/auth/check', [
            'google_token' => $email
        ]);

        // If strict role check, admin presumably fails unless explicit grant
        // We want to see what happens.
        if ($response->status() == 200) {
            echo "Superadmin ACCESS GRANTED\n";
        } else {
            echo "Superadmin ACCESS DENIED (" . $response->status() . ")\n";
        }
    }
}
