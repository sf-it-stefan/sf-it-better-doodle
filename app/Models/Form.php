<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Form extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'header_image',
        'settings',
        'active_until',
        'allow_edit',
        'is_active',
        'language',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'active_until' => 'datetime',
            'allow_edit' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = static::generateUniqueSlug($form->title);
            }
        });

        static::deleting(function (Form $form) {
            // Delete uploaded images for all entries
            $uploadDir = 'uploads/entries/' . $form->id;
            if (Storage::disk('public')->exists($uploadDir)) {
                Storage::disk('public')->deleteDirectory($uploadDir);
            }

            // Delete header image
            if ($form->header_image) {
                Storage::disk('public')->delete('uploads/headers/' . $form->header_image);
            }
        });
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FormEntry::class)->latest();
    }

    public function isExpired(): bool
    {
        return $this->active_until && $this->active_until->isPast();
    }

    public function isAcceptingResponses(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function getPublicUrl(): string
    {
        return url('/f/' . $this->slug);
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
