<?php

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * An editable email template. The class was called EmailTempalate; the table
 * keeps that spelling so existing installs need no migration.
 */
class EmailTemplate extends Model
{
    use BelongsToSchool;

    protected $table = 'email_tempalates';

    protected $fillable = [
        'type',
        'subject',
        'content',

    ];
}
