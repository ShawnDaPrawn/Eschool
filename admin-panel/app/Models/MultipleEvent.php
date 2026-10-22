<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultipleEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'event_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'description',
    ];

    public function events()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
