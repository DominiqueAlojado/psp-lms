<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LearningResource extends Model
{
    /** @use HasFactory<\Database\Factories\LearningResourceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'uploaded_by',
        'title',
        'description',
        'category',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'target_year_levels',
        'is_published',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'target_year_levels' => 'array',
            'is_published' => 'boolean',
            'file_size' => 'integer',
            'download_count' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }
}
