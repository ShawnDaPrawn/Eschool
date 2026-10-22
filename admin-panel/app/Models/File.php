<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['id', 'modal_type', 'modal_id', 'file_name', 'file_thumbnail', 'type', 'file_url'];

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    protected $appends = ['file_extension', 'type_detail'];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($file) { // before delete() method call this
            $file->deleteStoredFiles();
        });
    }

    /**
     * Remove the uploaded file and thumbnail from the public disk. The accessors
     * return URLs, so the stored paths are read raw (a URL never exists on disk).
     */
    public function deleteStoredFiles(): void
    {
        $paths = [$this->getRawOriginal('file_thumbnail')];
        if (in_array((int) $this->type, [1, 3], true)) { // File Upload, Video Upload
            $paths[] = $this->getRawOriginal('file_url');
        }
        foreach (array_filter($paths) as $path) {
            if (! preg_match('#^https?://#i', $path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function modal()
    {
        return $this->morphTo();
    }

    // Getter Attributes
    public function getFileUrlAttribute($value)
    {
        if ($this->type == 1 || $this->type == 3) {
            // IF type is File Upload or Video Upload then add Full URL.
            return url(Storage::url($value));
        } else {
            // ELSE return the value as it is.
            return $value;
        }
    }

    // Getter Attributes
    public function getFileThumbnailAttribute($value)
    {
        if (! empty($value)) {
            return url(Storage::url($value));
        } else {
            return '';
        }
    }

    public function getFileExtensionAttribute()
    {
        if (! empty($this->file_url)) {
            return pathinfo(url(Storage::url($this->file_url)), PATHINFO_EXTENSION);
        } else {
            return '';
        }
    }

    public function getTypeDetailAttribute()
    {
        // 1 = File Upload, 2 = Youtube Link, 3 = Video Upload, 4 = Other Link
        if ($this->type == 1) {
            return 'File Upload';
        } elseif ($this->type == 2) {
            return 'Youtube Link';
        } elseif ($this->type == 3) {
            return 'Video Upload';
        } elseif ($this->type == 4) {
            return 'Other Link';
        }
    }
}
