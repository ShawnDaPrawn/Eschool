<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Language;
use Illuminate\Support\Facades\File;
use Tests\Concerns\UsesDemoSchool;
use Tests\TestCase;

class SouthAfricanLanguagesTest extends TestCase
{
    use UsesDemoSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
    }

    public function test_both_catalogues_have_every_english_key_and_preserve_placeholders(): void
    {
        $english = json_decode(File::get(resource_path('lang/en.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach (['af', 'zu'] as $code) {
            $labels = json_decode(File::get(resource_path('lang/'.$code.'.json')), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(array_keys($english), array_keys($labels));
            foreach ($english as $key => $source) {
                $this->assertIsString($labels[$key], $code.' '.$key);
                $this->assertNotSame('', trim($labels[$key]), $code.' '.$key);
                $pattern = '/\{\{[^{}]*\}\}|\{[A-Za-z_][^{}]*\}|(?<!\w):[A-Za-z_]\w*|%(?:\d+\$)?[sdif]|<[^>]+>/';
                preg_match_all($pattern, $source, $before);
                preg_match_all($pattern, $labels[$key], $after);
                sort($before[0]);
                sort($after[0]);
                $this->assertSame($before[0], $after[0], $code.' '.$key);
            }
        }
    }

    public function test_native_language_labels_are_served_for_each_locale(): void
    {
        foreach (['af', 'zu'] as $code) {
            $this->withSession(['language' => (object) ['code' => $code], 'locale' => $code])
                ->get(route('common.language.read'))->assertOk()
                ->assertHeader('Content-Type', 'text/javascript; charset=UTF-8')
                ->assertContent('window.languageLabels = '.File::get(resource_path('lang/'.$code.'.json')));
        }
    }

    public function test_install_and_upgrade_register_languages_without_overwriting_custom_configuration(): void
    {
        foreach (['af', 'zu'] as $code) {
            $this->assertDatabaseHas('languages', ['code' => $code, 'file' => $code.'.json', 'is_rtl' => 0]);
        }
        $language = Language::where('code', 'af')->firstOrFail();
        $language->name = 'Existing school name';
        $language->save();
        $migration = require database_path('migrations/2026_10_24_110000_add_south_african_languages.php');
        $migration->up();
        $migration->up();
        $migration->down();
        $this->assertSame('Existing school name', $language->fresh()->name);
        $this->assertSame(1, Language::where('code', 'af')->count());
        $this->assertSame(1, Language::where('code', 'zu')->count());
    }
}
