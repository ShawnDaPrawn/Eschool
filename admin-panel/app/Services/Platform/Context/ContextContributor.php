<?php

declare(strict_types=1);

namespace App\Services\Platform\Context;

/**
 * One part of GET /api/v2/me/context. The contributors are listed in
 * config/context.php; each work stream adds its own and never edits
 * another's (engineering/phases/phase-1.md, "Contract between streams").
 *
 * contribute() returns top-level keys and their values (arrays, scalars or
 * JSON resources). Contributors run in the listed order and a later one's
 * object is merged into an earlier one's under the same key, so a
 * contributor may add fields inside another's object (for example the
 * school's branding inside current_school) but never removes one. Every
 * key and field must stay as openapi.yaml describes it (Context).
 */
interface ContextContributor
{
    /**
     * @return array<string, mixed>
     */
    public function contribute(ContextScope $scope): array;
}
