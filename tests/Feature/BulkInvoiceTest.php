<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\WalimuridProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BulkInvoiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating bulk invoices.
     */
    public function test_can_create_bulk_invoices_for_all_students()
    {
        // 1. Arrange: Create created needed data

        // Admin user to perform the action
        $admin = User::factory()->create();

        // Create a Walimurid User and Profile
        $waliUser = User::factory()->create();
        $waliProfile = WalimuridProfile::create([
            'user_id' => $waliUser->id,
            'fullname' => 'Pak Budi',
            'phone' => '08123456789',
            'address' => 'Jl. Merdeka No. 10'
        ]);

        $count = 5;
        // Create 5 students linked to the profile
        for ($i = 0; $i < $count; $i++) {
            Student::create([
                'walimurid_profile_id' => $waliProfile->id,
                'fullname' => "Student $i",
                'nis' => "NIS$i",
                'unit' => 'SD',
            ]);
        }

        // 2. Act: Call the endpoint
        $response = $this->actingAs($admin)
            ->postJson('/api/keuangan/invoices/bulk', [
                'amount' => 150000,
                'description' => 'SPP Bulan Januari 2025'
            ]);  // 3. Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => "Successfully created invoice for {$count} students",
                'count' => $count
            ]);

        // Check DB
        $this->assertDatabaseCount('invoices', $count);
        $this->assertDatabaseHas('invoices', [
            'amount' => 150000,
            'description' => 'SPP Bulan Januari 2025',
            'status' => 'UNPAID'
        ]);
    }
}
