<?php

namespace Aura\Base\Resources;

use Aura\Base\Models\Post;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Cache;

class Option extends Resource
{
    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'option';

    public static string $type = 'Option';

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = ['name', 'value', 'team_id'];

    protected static ?string $group = 'Aura';

    protected $table = 'options';

    public static function byName($name)
    {
        return static::where('name', $name)->first();
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Json',
                'validation' => 'required|json',
                'on_index' => false,
                'slug' => 'value',
                'decode' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return view('aura::components.icon.option')->render();
    }

    public static function getWidgets(): array
    {
        return [];
    }

    /**
     * Encode native values without interpreting application strings as JSON.
     *
     * JSON editor input is parsed by the field dehydration step before model
     * assignment. Keeping this mutator encoding-only preserves strings such
     * as "true" and "[]", while representing a native null as JSON `null`
     * for the non-nullable value column.
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (config('aura.teams')) {
            static::addGlobalScope(app(TeamScope::class));
        }

        static::saving(function ($option) {

            if (config('aura.teams') && ! isset($option->team_id) && auth()->user()) {
                $option->team_id = auth()->user()->current_team_id;
            }

            // unset post attributes
            unset($option->title);
            unset($option->content);
            unset($option->user_id);
            unset($option->type);
        });

        static::saved(fn ($option) => $option->forgetCachedValue());
        static::deleted(fn ($option) => $option->forgetCachedValue());
    }

    /**
     * Forget the cache entries that readers of this option populate.
     *
     * Options edited directly (e.g. in the admin) bypass the helpers that
     * normally invalidate these keys, so a change would otherwise stay
     * invisible until the cache expires.
     */
    protected function forgetCachedValue(): void
    {
        $name = (string) $this->name;

        Cache::forget($name);
        Cache::forget('aura.'.$name);

        // Aura::getOption() caches team options as "{teamId}.aura.{key}".
        if (preg_match('/^team\.(\d+)\.(.+)$/', $name, $matches)) {
            Cache::forget($matches[1].'.aura.'.$matches[2]);
        }

        // Wildcard lookups (e.g. "user.1.columns.*") cache under their prefix.
        $segments = explode('.', $name);

        while (count($segments) > 1) {
            array_pop($segments);
            Cache::forget(implode('.', $segments).'.*');
        }
    }
}
