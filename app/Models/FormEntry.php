<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FormEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_id',
        'edit_token',
        'data',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FormEntry $entry) {
            $form = $entry->form ?? Form::find($entry->form_id);
            if ($form && $form->allow_edit && empty($entry->edit_token)) {
                $entry->edit_token = Str::random(64);
            }
        });

        static::deleting(function (FormEntry $entry) {
            $uploadDir = 'uploads/entries/' . $entry->form_id . '/' . $entry->id;
            if (Storage::disk('public')->exists($uploadDir)) {
                Storage::disk('public')->deleteDirectory($uploadDir);
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function getEditUrl(): ?string
    {
        if (!$this->edit_token) {
            return null;
        }

        return url('/f/' . $this->form->slug . '/edit/' . $this->edit_token);
    }
}
