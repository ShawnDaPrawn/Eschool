<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TranslationsTest extends TestCase
{
    public function test_every_language_file_is_a_flat_map_of_labels(): void
    {
        $files = File::glob(resource_path('lang/*.json'));
        $this->assertContains(resource_path('lang/en.json'), $files, 'English is the fallback language and must exist');

        foreach ($files as $file) {
            $labels = json_decode(File::get($file), true);
            $this->assertIsArray($labels, basename($file).' is not valid JSON');
            foreach ($labels as $key => $label) {
                $this->assertIsString($label, basename($file)." has a non-text label for '$key'");
            }
        }
    }

    public function test_the_admin_panel_gets_labels_for_the_chosen_language(): void
    {
        $this->withSession(['language' => (object) ['code' => 'ar']])
            ->get(route('common.language.read'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/javascript; charset=UTF-8')
            ->assertContent('window.languageLabels = '.File::get(resource_path('lang/ar.json')));
    }

    public function test_a_language_without_a_file_falls_back_to_english(): void
    {
        $english = 'window.languageLabels = '.File::get(resource_path('lang/en.json'));

        // A languages row can outlive its file, e.g. the test languages removed from resources/lang.
        $this->withSession(['language' => (object) ['code' => 'w3']])
            ->get(route('common.language.read'))->assertOk()->assertContent($english);
        $this->get(route('common.language.read'))->assertOk()->assertContent($english);
        $this->withSession(['language' => (object) ['code' => '../../.env']])
            ->get(route('common.language.read'))->assertOk()->assertContent($english);
    }
}
