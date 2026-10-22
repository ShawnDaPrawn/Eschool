<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An editable email template. The class was called EmailTempalate; the table
 * keeps that spelling so existing installs need no migration.
 */
class EmailTemplate extends Model
{
    protected $table = 'email_tempalates';

    protected $fillable = [
        'type',
        'subject',
        'content',

    ];
}
