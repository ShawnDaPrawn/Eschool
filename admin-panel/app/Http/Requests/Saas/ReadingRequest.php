<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Reading\ReadingAccess;
use App\Services\Reading\ReadingCatalogue;

/**
 * The /saas/reading forms (Action Plan 4.4). Every one needs reading.manage;
 * the services check the ability and validate the same rules again.
 */
class ReadingRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(ReadingAccess::class)->canHq($this->user());
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.reading.books.store' => ReadingCatalogue::bookRules(),
            'saas.reading.books.update' => ReadingCatalogue::updateRules((int) $this->route('book')),
            'saas.reading.books.rights' => ReadingCatalogue::rightsRules(),
            'saas.reading.questions.store' => ReadingCatalogue::questionRules(),
            'saas.reading.shelves.store' => ReadingCatalogue::shelfRules(),
            'saas.reading.shelves.update' => ReadingCatalogue::shelfUpdateRules(),
            'saas.reading.shelves.books' => ReadingCatalogue::shelfBookRules(),
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        $route = (string) $this->route()?->getName();
        // A shelf's books are typed as catalogue numbers in order, separated by commas or new lines.
        if ($route === 'saas.reading.shelves.books' && ! $this->has('book_ids')) {
            $list = trim((string) $this->input('book_list', ''));
            $this->merge(['book_ids' => $list === '' ? [] : array_values(array_filter(preg_split('/[\s,]+/', $list) ?: [], fn ($id) => $id !== ''))]);
        }
        // A question's options are five optional boxes; blank ones are dropped and the right answer follows its box.
        if ($route === 'saas.reading.questions.store' && ! $this->has('options')) {
            $options = [];
            $map = [];
            foreach (range(1, 5) as $box) {
                $value = trim((string) $this->input('option_'.$box, ''));
                if ($value !== '') {
                    $map[$box] = count($options);
                    $options[] = $value;
                }
            }
            $this->merge(['options' => $options, 'answer' => $map[(int) $this->input('right_option')] ?? 9]);
        }
    }
}
