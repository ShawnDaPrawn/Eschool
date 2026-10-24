<?php

declare(strict_types=1);
use App\Models\Academics\ContentVersion;
use App\Services\Academics\Content\ContentPipeline;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

// UI rehearsal only; scanner/converter protocols have separate integration tests.
app(CurrentSchool::class)->run(1, function () use ($contentTitle) {
    $version = ContentVersion::where('state', 'queued')->orderByDesc('id')->get()->first(fn ($v) => $v->metadata['title'] === $contentTitle);
    if ($version === null) {
        throw new RuntimeException('Synthetic browser content was not queued.');
    }
    app()->instance(UploadScanner::class, new class implements UploadScanner
    {
        public function scan(string $bytes): array
        {
            return ['clean' => true, 'engine' => 'Synthetic browser current', 'signature' => null];
        }
    });
    Http::swap(new Factory);
    Http::preventStrayRequests();
    config(['academic_content.converter_url' => 'https://converter.example', 'academic_content.converter_token' => str_repeat('synthetic-content-', 3), 'academic_content.source_origin' => 'https://backend.example']);
    Http::fake(['https://converter.example/*' => fn ($r) => Http::response(['markdown' => "# Fractions\n\nOne half is two quarters.", 'source_sha256' => $r['source_sha256'], 'generation_id' => $r['generation_id'], 'duration_seconds' => 0, 'language' => null, 'review_required' => true])]);
    app(ContentPipeline::class)->run((int) $version->id, $version->generation_id);
});
