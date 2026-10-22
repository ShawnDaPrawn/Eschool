<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Audit\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use Auditable, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'message',
    ];

    /**
     * Some settings rows still hold secrets (mail password, gateway keys, the
     * service account path); the audit log keeps that they changed, not the
     * value.
     */
    protected function auditRedact(array $values): array
    {
        $type = (string) ($values['type'] ?? $this->getAttribute('type') ?? '');
        if (array_key_exists('message', $values) && preg_match('/pass|secret|key|token|private|credential|signature|service_account/i', $type)) {
            $values['message'] = '[redacted]';
        }

        return $values;
    }
}
