<?php

namespace App\Models;

use App\Enums\FieldType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_id',
        'type',
        'label',
        'description',
        'options',
        'required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'options' => 'array',
            'required' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
