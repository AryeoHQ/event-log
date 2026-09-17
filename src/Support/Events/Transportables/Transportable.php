<?php

declare(strict_types=1);

namespace Support\Events\Transportables;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Support\Events\Log\Transports\Contracts\Transport;
use Tooling\Composer\ClassMap\Cache;
use Tooling\EventLog\Composer\ClassMap\Collectors\Transports;

/**
 * @property string $alias
 * @property class-string<\Support\Events\Log\Transports\Contracts\Transport> $class
 * @property \Illuminate\Support\Collection<int, class-string<\Support\Events\Log\Transports\Contracts\Transport>> $transports
 */
class Transportable extends Model
{
    use \Sushi\Sushi;

    public $incrementing = false;

    protected $primaryKey = 'alias';

    protected $keyType = 'string';

    protected $casts = [
        'transports' => AsCollection::class,
    ];

    /**
     * @var array<string, string>
     */
    protected $schema = [
        'alias' => 'string',
        'class' => 'string',
        'transports' => 'json',
    ];

    /**
     * @return array<int, array{alias: string, class: class-string, transports: string}>
     */
    public function getRows(): array
    {
        return collect(resolve(Cache::class)->get(Transports::class) ?? [])
            ->map(fn (string $class): Transport => (new ReflectionClass($class))->newInstanceWithoutConstructor())
            ->map(fn (Transport $event): array => [
                'alias' => (string) $event->alias,
                'class' => $event::class,
                'transports' => json_encode($event->transports->values(), JSON_THROW_ON_ERROR),
            ])
            ->values()
            ->all();
    }

    protected function sushiShouldCache(): bool
    {
        return true;
    }

    protected function sushiCacheReferencePath(): string
    {
        // Load first so a changed source rebuilds the classmap before Sushi checks its date.
        return tap(
            resolve(Cache::class),
            fn (Cache $cache): array => $cache->loaded
        )->cachePath;
    }
}
