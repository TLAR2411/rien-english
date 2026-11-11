<?php

namespace Tests\Feature\Learning\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    private function upload(string $type, UploadedFile $file)
    {
        return $this->post('/api/web/admin/uploads', ['type' => $type, 'file' => $file], ['Accept' => 'application/json']);
    }

    public function test_teacher_can_upload_an_mp3(): void
    {
        $response = $this->upload('audio', UploadedFile::fake()->create('i-am.mp3', 500, 'audio/mpeg'))->assertCreated();

        Storage::disk('public')->assertExists($response->json('data.path'));
        $this->assertStringStartsWith('learning/audio/', $response->json('data.path'));
        $this->assertStringEndsWith('.mp3', $response->json('data.url'));
    }

    public function test_teacher_can_upload_an_image(): void
    {
        $this->upload('image', UploadedFile::fake()->image('table.png'))->assertCreated();
    }

    public function test_audio_over_10_mb_is_rejected(): void
    {
        $this->upload('audio', UploadedFile::fake()->create('long.mp3', 10 * 1024 + 1, 'audio/mpeg'))
            ->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_image_over_2_mb_is_rejected(): void
    {
        $this->upload('image', UploadedFile::fake()->image('big.jpg')->size(2 * 1024 + 1))
            ->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_wrong_file_types_are_rejected(): void
    {
        $this->upload('audio', UploadedFile::fake()->create('virus.php', 10, 'application/x-php'))
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->upload('image', UploadedFile::fake()->create('song.mp3', 10, 'audio/mpeg'))
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->upload('video', UploadedFile::fake()->create('a.mp4', 10, 'video/mp4'))
            ->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_student_cannot_upload(): void
    {
        Passport::actingAs($this->userWithRole('student'));

        $this->upload('image', UploadedFile::fake()->image('x.png'))->assertForbidden();
    }
}
