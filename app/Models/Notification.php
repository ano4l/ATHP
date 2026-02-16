<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'read',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('read', false);
    }

    public static function notify(User $user, string $type, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
    }
}
