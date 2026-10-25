<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardChipProfile extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'card_chip_profiles';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['uid', 'uid_hash', 'metadata_key', 'file_read_key', 'encoding_package'];

    protected function casts(): array
    {
        return ['uid' => 'encrypted', 'metadata_key' => 'encrypted', 'file_read_key' => 'encrypted', 'last_counter' => 'integer', 'key_version' => 'integer', 'encoded_at' => UtcInstant::class];
    }
}
