<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The <x-m3.*> Blade components (resources/views/components/m3) that admin and website
 * pages are built from. Each test renders a component the way pages use it and checks
 * the markup the M3 library (public/assets/m3) and the shared scripts depend on.
 */
class M3ComponentsTest extends TestCase
{
    /** Renders a component, with runs of whitespace collapsed so the checks read as markup. */
    private function render(string $template, array $data = []): string
    {
        return preg_replace('/\s+/', ' ', Blade::render($template, $data));
    }

    public function test_a_text_field_puts_layout_on_the_wrapper_and_everything_else_on_the_input(): void
    {
        $html = $this->render(
            '<x-m3.text-field name="name" id="name" label="Name" required class="m3-col-6" input-class="datepicker-popup" maxlength="16" data-x="1" />'
        );

        $this->assertMatchesRegularExpression('/<div class="m3-col-6">\s*<label class="m3-text-field">/', $html);
        $this->assertMatchesRegularExpression('/<input type="text" name="name" id="name" value=""\s+required/', $html);
        $this->assertStringContainsString('class="m3-text-field__input datepicker-popup"', $html);
        $this->assertStringContainsString('maxlength="16"', $html);
        $this->assertStringContainsString('data-x="1"', $html);
        $this->assertStringContainsString('placeholder=" "', $html);
        $this->assertStringContainsString('<span class="m3-text-field__required" aria-hidden="true">*</span>', $html);
    }

    public function test_a_text_field_links_its_hint_and_shows_an_error(): void
    {
        $hint = $this->render('<x-m3.text-field name="email" id="email" type="email" label="Email" hint="We reply here." />');
        $this->assertStringContainsString('aria-describedby="email-supporting"', $hint);
        $this->assertStringContainsString('<p class="m3-text-field__supporting" id="email-supporting">We reply here.</p>', $hint);

        $error = $this->render('<x-m3.text-field name="email" id="email" label="Email" error="Enter an email." />');
        $this->assertStringContainsString('m3-text-field--error', $error);
        $this->assertStringContainsString('aria-invalid="true"', $error);
        $this->assertStringContainsString('m3-text-field__supporting--error', $error);
        $this->assertStringContainsString('Enter an email.', $error);
    }

    public function test_a_text_field_can_be_a_textarea_and_escapes_what_it_shows(): void
    {
        $html = $this->render(
            '<x-m3.text-field name="note" type="textarea" rows="2" :label="$label" :value="$value" />',
            ['label' => '<b>Note</b>', 'value' => '</textarea><script>alert(1)</script>']
        );

        $this->assertMatchesRegularExpression('/<textarea name="note" rows="2"/', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Note</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;Note&lt;/b&gt;', $html);
    }

    public function test_a_select_marks_the_chosen_options(): void
    {
        $single = $this->render(
            '<x-m3.select name="medium_id" label="Medium" :options="$options" :value="2" placeholder="Select" required />',
            ['options' => collect([1 => 'English', 2 => 'Hindi'])]
        );
        $this->assertStringContainsString('<option value="">Select</option>', $single);
        $this->assertStringContainsString('<option value="2" selected>Hindi</option>', $single);
        $this->assertStringContainsString('<option value="1" >English</option>', $single);
        $this->assertStringContainsString('m3-text-field--select', $single);

        $multiple = $this->render(
            '<x-m3.select name="ids[]" label="Subjects" :options="[1 => \'Maths\', 2 => \'Science\', 3 => \'Art\']" :value="[\'1\', 3]" multiple />'
        );
        $this->assertStringContainsString('multiple', $multiple);
        $this->assertStringContainsString('<option value="1" selected>Maths</option>', $multiple);
        $this->assertStringContainsString('<option value="3" selected>Art</option>', $multiple);
        $this->assertStringContainsString('<option value="2" >Science</option>', $multiple);
    }

    public function test_choice_groups_check_the_current_values(): void
    {
        $radios = $this->render(
            '<x-m3.radio-group name="status" legend="Status" :options="[1 => \'Enable\', 0 => \'Disable\']" :value="0" required />'
        );
        $this->assertStringContainsString('<fieldset class="m3-fieldset">', $radios);
        $this->assertMatchesRegularExpression('/value="0"\s+checked\s+required/', $radios);
        $this->assertMatchesRegularExpression('/value="1"\s+required/', $radios);

        $checkboxes = $this->render(
            '<x-m3.checkbox-group name="days" legend="Days" :options="[\'mon\' => \'Monday\', \'tue\' => \'Tuesday\']" :value="[\'tue\']" />'
        );
        $this->assertStringContainsString('name="days[]"', $checkboxes);
        $this->assertMatchesRegularExpression('/value="tue"\s+checked/', $checkboxes);
        $this->assertDoesNotMatchRegularExpression('/value="mon"\s+checked/', $checkboxes);
        $this->assertStringNotContainsString('data-rule-required', $checkboxes);
    }

    public function test_a_required_checkbox_group_asks_for_one_box_not_every_box(): void
    {
        $html = $this->render(
            '<x-m3.checkbox-group name="days" legend="Days" :options="[\'mon\' => \'Monday\', \'tue\' => \'Tuesday\']" required />'
        );

        // A native `required` on each box would make every box mandatory.
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*\srequired[\s>]/', $html);
        $this->assertSame(2, substr_count($html, 'data-rule-required="true"'));
        $this->assertStringContainsString('<span class="m3-text-field__required" aria-hidden="true">*</span>', $html);
    }

    public function test_a_file_field_keeps_the_hooks_the_upload_scripts_use(): void
    {
        $html = $this->render('<x-m3.file-field name="image" label="Image" accept="image/*" required info-id="image" input-class="student_image" />');

        $this->assertStringContainsString('class="m3-file-field"', $html);
        $this->assertMatchesRegularExpression('/<input type="file" name="image"[^>]*accept="image\/\*"[^>]*required[^>]*class="file-upload-default m3-visually-hidden student_image"/', $html);
        $this->assertStringContainsString('class="m3-text-field__input file-upload-info" id="image" disabled', $html);
        $this->assertStringContainsString('file-upload-browse', $html);
    }

    public function test_a_file_fields_hint_describes_the_file_input(): void
    {
        $html = $this->render('<x-m3.file-field name="image" id="image" label="Image" hint="120x120px, square." />');

        $this->assertMatchesRegularExpression('/<input type="file" name="image" id="image"[^>]*aria-describedby="image-supporting"/', $html);
        $this->assertStringContainsString('<p class="m3-text-field__supporting" id="image-supporting">120x120px, square.</p>', $html);
    }

    public function test_a_dialog_with_a_form_has_its_ids_a_token_and_its_actions(): void
    {
        $html = $this->render(
            '<x-m3.dialog id="editModal" title="Edit medium" form-action="/medium" form-id="edit-form" form-class="medium-edit-form" submit="Save"><input name="id" id="id"></x-m3.dialog>'
        );

        $this->assertStringContainsString('<dialog id="editModal" aria-labelledby="editModal-title"', $html);
        $this->assertStringContainsString('class="m3-dialog"', $html);
        $this->assertStringContainsString('<form class="m3-dialog__form medium-edit-form" id="edit-form"', $html);
        $this->assertStringContainsString('action="/medium" method="POST" novalidate', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('<h2 class="m3-dialog__headline" id="editModal-title">Edit medium</h2>', $html);
        $this->assertStringContainsString('data-m3-dialog-close', $html);
        $this->assertMatchesRegularExpression('/<button type="submit" class="m3-button m3-button--filled">Save<\/button>/', $html);
    }

    public function test_buttons_are_links_when_they_have_an_href(): void
    {
        $this->assertStringContainsString(
            '<a href="/students" class="m3-button m3-button--text">Back</a>',
            $this->render('<x-m3.button variant="text" href="/students">Back</x-m3.button>')
        );
        $this->assertMatchesRegularExpression(
            '/<button type="submit" class="m3-button m3-button--filled m3-button--icon-leading"><svg[^>]*class="lc-icon m3-icon"/',
            $this->render('<x-m3.button type="submit" icon="plus">Add</x-m3.button>')
        );
        $iconButton = $this->render('<x-m3.icon-button icon="trash" label="Delete" variant="tonal" class="delete-form" />');
        $this->assertStringContainsString('aria-label="Delete" data-m3-tooltip="Delete" class="m3-icon-button m3-icon-button--tonal delete-form"', $iconButton);
    }

    public function test_a_card_has_a_header_with_its_actions(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-m3.card title="Summary" subtitle="This term" class="m3-col-12">
                <x-slot:actions><x-m3.button variant="text">Add</x-m3.button></x-slot:actions>
                Body
            </x-m3.card>
            BLADE);

        $this->assertStringContainsString('<section class="m3-card m3-card--outlined m3-col-12">', $html);
        $this->assertStringContainsString('<h2 class="m3-card__headline">Summary</h2>', $html);
        $this->assertStringContainsString('<p class="m3-card__subhead">This term</p>', $html);
        $this->assertStringContainsString('m3-card__header-actions', $html);
    }
}
