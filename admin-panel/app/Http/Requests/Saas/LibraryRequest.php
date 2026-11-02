<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Learning\Library\LibraryAccess;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Learning\Library\LibraryReviewers;
use App\Services\Learning\Library\LibrarySchools;

/**
 * The /saas/library forms (Action Plan 3.3, SA.16). Reviewing needs
 * library.review; everything else needs library.manage. The services check
 * the ability and validate the same rules again.
 */
class LibraryRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        $ability = $this->route()?->getName() === 'saas.library.versions.review' ? LibraryAccess::REVIEW : LibraryAccess::MANAGE;

        return parent::authorize() && app(LibraryAccess::class)->can($this->user(), $ability);
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.library.frameworks.store' => LibraryCatalogue::frameworkRules(),
            'saas.library.sources.store' => LibraryCatalogue::sourceRules(),
            'saas.library.topics.store' => LibraryCatalogue::topicRules(),
            'saas.library.topics.prerequisites' => LibraryCatalogue::prerequisiteRules(),
            'saas.library.items.store' => LibraryItems::createRules(),
            'saas.library.items.draft' => LibraryItems::draftRules(),
            'saas.library.items.submit' => LibraryItems::stepRules(),
            'saas.library.items.publish' => LibraryItems::publishRules(),
            'saas.library.versions.review' => LibraryItems::reviewRules(),
            'saas.library.reviewers.store' => LibraryReviewers::assignRules(),
            'saas.library.schools.switch' => LibrarySchools::switchRules(),
            default => [],
        };
    }

    /** An empty multi-select sends nothing: treat it as "no prerequisites". */
    protected function prepareForValidation(): void
    {
        if ($this->route()?->getName() === 'saas.library.topics.prerequisites' && ! $this->has('prerequisite_ids')) {
            $this->merge(['prerequisite_ids' => []]);
        }
        // A diagnostic's questions are typed as item numbers, one per line or separated by commas.
        if (is_string($this->input('item_list')) && trim($this->input('item_list')) !== '' && ! $this->has('item_ids')) {
            $this->merge(['item_ids' => array_values(array_filter(preg_split('/[\s,]+/', trim($this->input('item_list'))) ?: [], fn ($id) => $id !== ''))]);
        }
    }
}
