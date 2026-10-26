<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class TeachingDraftExports
{
    public function output(User $actor, string $id, bool $answers = false): string
    {
        $service = app(TeachingDrafts::class);
        $draft = $service->view($actor, $id);
        abort_unless(in_array($draft['state'], ['approved', 'published', 'applied'], true) && is_array($draft['definition']), 409, 'Approve this draft before exporting it.');
        $hash = hash('sha256', json_encode($draft, JSON_THROW_ON_ERROR));
        $locale = app()->getLocale();
        try {
            app()->setLocale($draft['request']['language']);
            $bytes = Pdf::loadView('learning.teaching-draft', compact('draft', 'answers'))->setPaper('a4')
                ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans'])->output();
        } finally {
            app()->setLocale($locale);
        }
        $fresh = $service->view($actor, $id);
        abort_unless(hash_equals($hash, hash('sha256', json_encode($fresh, JSON_THROW_ON_ERROR))), 409, 'The approved draft changed. Review it before exporting again.');

        return $bytes;
    }
}
