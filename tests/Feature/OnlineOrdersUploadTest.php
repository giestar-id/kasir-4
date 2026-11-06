<?php

namespace Tests\Feature;

use App\Livewire\OnlineOrders\Index as OnlineOrdersIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineOrdersUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_multiple_screenshots(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'owner']);
        $this->actingAs($user);

        $file1 = UploadedFile::fake()->image('gofood_1.png', 800, 1000);
        $file2 = UploadedFile::fake()->image('gofood_2.png', 800, 1000);

        $comp = Livewire::test(OnlineOrdersIndex::class)
            ->set('uploadedFiles', [$file1, $file2])
            ->assertSee('file siap diproses')
            ->call('removeUploadedFile', 1);

        $this->assertCount(1, $comp->get('uploadedFiles'));

        $comp->call('uploadScreenshots')
            ->assertDispatched('close-modal', 'upload-screenshot-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseCount('screenshot_imports', 1);
    }

    public function test_upload_empty_guard(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $this->actingAs($user);

        Livewire::test(OnlineOrdersIndex::class)
            ->set('uploadedFiles', [])
            ->call('uploadScreenshots')
            ->assertDispatched('toast');
    }
}
