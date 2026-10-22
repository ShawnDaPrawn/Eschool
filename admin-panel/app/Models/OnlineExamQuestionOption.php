<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineExamQuestionOption extends Model
{
    use BelongsToSchool, HasFactory;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];
}
