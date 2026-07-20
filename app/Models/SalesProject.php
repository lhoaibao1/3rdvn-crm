<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SalesProject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'crm_module_id',
        'name',
        'slug',
        'code_prefix',
        'description',
        'lead_form_schema',
        'module_form_schema',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'crm_module_id' => 'integer',
        'lead_form_schema' => 'array',
        'module_form_schema' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (SalesProject $project): void {
            if (blank($project->slug) && filled($project->name)) {
                $project->slug = Str::slug($project->name);
            }

            $project->lead_form_schema = self::normalizeFieldSchema($project->lead_form_schema);
            $project->module_form_schema = self::normalizeFieldSchema($project->module_form_schema);
        });
    }


    public static function normalizeFieldSchema(mixed $schema): array
    {
        if (! is_array($schema)) {
            return [];
        }

        return collect($schema)
            ->filter(fn (mixed $field): bool => is_array($field))
            ->map(function (array $field): array {
                $field['options'] = self::normalizeOptionItems($field['options'] ?? []);

                return $field;
            })
            ->values()
            ->all();
    }

    public static function normalizeOptionItems(mixed $options): array
    {
        if (blank($options)) {
            return [];
        }

        if (is_string($options)) {
            $options = preg_split('/\r\n|\r|\n/', $options) ?: [];
        }

        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->map(function (mixed $option): ?array {
                if (is_array($option)) {
                    $option = $option['value'] ?? $option['label'] ?? reset($option);
                }

                $option = trim((string) $option);

                return $option !== '' ? ['value' => $option] : null;
            })
            ->filter()
            ->unique('value')
            ->values()
            ->all();
    }

    public function crmModule(): BelongsTo
    {
        return $this->belongsTo(CrmModule::class, 'crm_module_id');
    }
}
