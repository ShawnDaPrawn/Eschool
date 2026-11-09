<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_video_writes_reject_markup_after_a_valid_video_id(): void
    {
        $payload = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ<img src=x onerror=alert(1)>';
        $this->actingAs($this->superAdmin())->postJson('/videos/store', ['youtube_url' => $payload])
            ->assertOk()->assertJson(['error' => true]);
        $this->assertDatabaseMissing('medias', ['youtube_url' => $payload]);

        $media = Media::forceCreate(['type' => 2, 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $this->postJson('/videos/update/'.$media->id, ['edit_id' => $media->id, 'youtube_url' => $payload])
            ->assertOk()->assertJson(['error' => true]);
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $media->fresh()->youtube_url);
    }

    public function test_existing_video_markup_is_rendered_as_text(): void
    {
        $payload = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ<img src=x onerror=alert(1)>';
        Media::forceCreate(['type' => 2, 'youtube_url' => $payload]);

        $row = $this->actingAs($this->superAdmin())->getJson('/videos-list')->assertOk()->json('rows.0');
        $this->assertSame($payload, $row['original_url']);
        $this->assertStringNotContainsString('<img', $row['url']);
        $this->assertStringContainsString(e($payload), $row['url']);
    }

    public function test_a_valid_video_can_be_saved_and_listed(): void
    {
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10';
        $this->actingAs($this->superAdmin())->postJson('/videos/store', ['youtube_url' => $url])
            ->assertOk()->assertJson(['error' => false]);
        $row = $this->getJson('/videos-list')->assertOk()->json('rows.0');
        $this->assertSame($url, $row['original_url']);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $row['url']);
    }

    public function test_a_user_without_media_permissions_cannot_write_or_list_videos(): void
    {
        $teacher = User::create([
            'first_name' => 'Media', 'last_name' => 'Teacher', 'email' => 'media-teacher@example.com',
            'gender' => 'Male', 'mobile' => '0000000000', 'password' => bcrypt('Synthetic-media-2026'),
        ]);
        $teacher->assignRole('Teacher');
        $url = 'https://youtu.be/dQw4w9WgXcQ';
        $this->actingAs($teacher)->post('/videos/store', ['youtube_url' => $url])
            ->assertRedirect()->assertSessionHasErrors('message');
        $this->get('/videos-list')->assertRedirect()->assertSessionHasErrors('message');
        $this->assertDatabaseMissing('medias', ['youtube_url' => $url]);
    }

    public function test_an_admin_cannot_read_or_change_another_schools_video(): void
    {
        School::forceCreate(['id' => 2, 'name' => 'Other School', 'slug' => 'other-school']);
        $url = 'https://youtu.be/dQw4w9WgXcQ';
        $foreign = app(CurrentSchool::class)->run(2, fn () => Media::forceCreate(['type' => 2, 'youtube_url' => $url]));

        $this->actingAs($this->superAdmin())->getJson('/videos-list')->assertOk()->assertJsonPath('rows', []);
        $this->postJson('/videos/update/'.$foreign->id, [
            'edit_id' => $foreign->id, 'youtube_url' => 'https://youtu.be/aaaaaaaaaaa',
        ])->assertOk()->assertJson(['error' => true]);
        $this->assertDatabaseHas('medias', ['id' => $foreign->id, 'school_id' => 2, 'youtube_url' => $url]);
    }
}
