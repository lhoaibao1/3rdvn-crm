<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AffiliateCampaign extends Model
{
    protected $fillable = ['name', 'slug', 'logo_url', 'summary', 'details', 'tracking_url', 'tracking_url_ios', 'attribution_param', 'is_active', 'opens_at', 'closes_at', 'closure_message', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('closes_at')->orWhere('closes_at', '>', now()));
    }

    public function isOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return (! $this->opens_at || $this->opens_at->lte($now))
            && (! $this->closes_at || $this->closes_at->gt($now));
    }

    public function closureReason(): string
    {
        return trim((string) $this->closure_message) ?: 'Dự án tạm dừng.';
    }
}
