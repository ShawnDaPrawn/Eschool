<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Learning\Marking\Accuracy\AccuracyAccess;
use App\Services\Learning\Marking\Accuracy\EvaluationImports;
use App\Services\Learning\Marking\Accuracy\MarkingGate;

/**
 * The /saas/marking-accuracy forms (Action Plan 3.18). Imports and runs need
 * marking.evaluate; switching a subject on needs marking.approve; switching
 * off either. The services check the ability and validate again.
 */
class MarkingAccuracyRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        $access = app(AccuracyAccess::class);

        return parent::authorize() && match ((string) $this->route()?->getName()) {
            'saas.marking-accuracy.gate.on' => $access->can($this->user(), AccuracyAccess::APPROVE),
            'saas.marking-accuracy.gate.off' => $access->can($this->user(), AccuracyAccess::APPROVE, AccuracyAccess::EVALUATE),
            default => $access->can($this->user(), AccuracyAccess::EVALUATE),
        };
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.marking-accuracy.papers.store' => EvaluationImports::formRules('paper'),
            'saas.marking-accuracy.scripts.store' => EvaluationImports::formRules('scripts'),
            'saas.marking-accuracy.gate.on' => MarkingGate::switchOnRules(),
            'saas.marking-accuracy.gate.off' => MarkingGate::switchOffRules(),
            default => [],
        };
    }
}
