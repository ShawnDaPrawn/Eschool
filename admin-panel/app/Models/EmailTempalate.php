<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTempalate extends Model
{
    //
    protected $fillable = [
        'type',
        'subject',
        'content',

    ];
}
