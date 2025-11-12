<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SubmissionFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'submission_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'download_count',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function deleteFile(): bool
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->delete($this->file_path);
        }

        return false;
    }

    protected static function boot(): void
    {
        parent::boot();

        // Delete file from storage when model is deleted
        static::deleting(function (SubmissionFile $file) {
            $file->deleteFile();
        });
    }
}
