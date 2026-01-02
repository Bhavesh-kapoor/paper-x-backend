<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile()
    {
        // Storage::fake('public'); // Not using Storage facade anymore

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $file = UploadedFile::fake()->create('certificate.pdf', 100);
        $avatar = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/user/profile', [
            'name' => 'Updated Name',
            'company_name' => 'Updated Company',
            'mobile' => '9876543210',
            'state' => 'Delhi',
            'udyam_certificate' => $file,
            'avatar' => $avatar,
            'operation_area' => 'local',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.company_name', 'Updated Company')
            ->assertJsonPath('data.mobile', '9876543210');
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'company_name' => 'Updated Company',
            'mobile' => '9876543210',
            'state' => 'Delhi',
        ]);

        // Assert file was stored
        $user->refresh();
        
        $this->assertFileExists(public_path($user->udyam_certificate));
        $this->assertFileExists(public_path($user->avatar));

        // Cleanup
        if (File::exists(public_path($user->udyam_certificate))) {
            File::delete(public_path($user->udyam_certificate));
        }
        if (File::exists(public_path($user->avatar))) {
            File::delete(public_path($user->avatar));
        }
    }

    public function test_user_cannot_update_profile_with_invalid_data()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/user/profile', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }
}
