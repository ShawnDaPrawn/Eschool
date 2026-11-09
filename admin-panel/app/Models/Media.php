<?php

declare(strict_types=1);

namespace App\Models;

use App\Rules\YouTubeUrl;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'medias';

    protected $appends = [
        'embeded_url',
    ];

    public function getThumbnailAttribute($value)
    {
        return url(Storage::url($value));
    }

    public function files()
    {
        return $this->hasMany(MediaFile::class);
    }

    public function getEmbededUrlAttribute()
    {
        $result = ['embedUrl' => null, 'thumbnailUrl' => null];
        if (! empty($this->youtube_url)) {
            $videoId = YouTubeUrl::videoId($this->youtube_url);
            if ($videoId !== null) {
                // Construct Embed URL
                $result['embedUrl'] = "https://www.youtube.com/embed/$videoId";
                $result['thumbnailUrl'] = "https://img.youtube.com/vi/$videoId/hqdefault.jpg";

                return $result;
            }

            // Return null if URL doesn't match the pattern
            return null;
        }

        return '';
    }

    // public function getOriginalUrlAttribute() {
    //     return $this->getRawOriginal('youtube_url');
    // }
}
