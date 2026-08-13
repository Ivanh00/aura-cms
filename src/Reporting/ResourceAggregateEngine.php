<?php

namespace Aura\Base\Reporting;

use Aura\Base\Aura;
use Aura\Base\Contracts\DeclaresReportingQueryScopes;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Support\ExactDecimal;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class ResourceAggregateEngine implements AggregateEngine
{
    private const MAX_BUCKETS = 400;

    private const MAX_GROUPS = 100;

    public function run(AggregateDefinition $definition): AggregateResult
    {
        $resource = $this->resource($definition->resource);
        Gate::authorize('viewAny', $resource);

        if ($definition->groupBy !== null && $definition->bucket !== null) {
            throw new InvalidArgumentException('Reporting cannot group by a field and a time bucket in the same request.');
        }

        $query = $this->authorizedQuery($resource);
        $this->applyQueryScope($query, $resource, $definition->queryScope);
        $this->applyRange($query, $resource, $definition->range);

        $metric = $this->metricExpression($query, $resource, $definition);

        if ($definition->groupBy !== null) {
            return new AggregateResult(null, $this->grouped($query, $resource, $definition, $metric));
        }

        if ($definition->bucket !== null) {
            return new AggregateResult(null, $this->bucketed($query, $resource, $definition, $metric));
        }

        return new AggregateResult($this->aggregate($query, $definition->operation, $metric));
    }

    /** @param Builder<\Aura\Base\Resource> $query */
    private function aggregate(Builder $query, AggregateOperation $operation, ?string $metric): int|string|null
    {
        $row = $this->aggregateSelect($query, $operation, $metric)->toBase()->first();

        if (! is_object($row)) {
            throw new RuntimeException('Reporting aggregate did not return a result row.');
        }

        return $this->aggregateFromRow($row, $operation);
    }

    private function aggregateFromRow(object $row, AggregateOperation $operation): int|string|null
    {
        if ($operation === AggregateOperation::Count) {
            return (int) $row->row_count;
        }

        $validCount = (int) $row->valid_count;

        if ($validCount === 0) {
            return null;
        }

        return match ($operation) {
            AggregateOperation::Average => $this->average((string) $row->scaled_sum, $validCount),
            AggregateOperation::Maximum => $this->decimal((string) $row->scaled_max),
            AggregateOperation::Minimum => $this->decimal((string) $row->scaled_min),
            AggregateOperation::Sum => $this->decimal((string) $row->scaled_sum),
            AggregateOperation::Count => (int) $row->row_count,
        };
    }

    /** @param Builder<\Aura\Base\Resource> $query */
    private function aggregateSelect(Builder $query, AggregateOperation $operation, ?string $metric): Builder
    {
        $query->selectRaw('COUNT(*) as row_count');

        if ($operation === AggregateOperation::Count) {
            return $query;
        }

        if ($metric === null) {
            throw new LogicException('Numeric reporting requires a metric expression.');
        }

        return $query
            ->selectRaw("COUNT({$metric}) as valid_count")
            ->selectRaw("SUM({$metric}) as scaled_sum")
            ->selectRaw("MIN({$metric}) as scaled_min")
            ->selectRaw("MAX({$metric}) as scaled_max");
    }

    /** @param Builder<\Aura\Base\Resource> $query */
    private function applyQueryScope(Builder $query, Resource $resource, ?string $scope): void
    {
        if ($scope === null) {
            return;
        }

        $declaredScopes = $resource instanceof DeclaresReportingQueryScopes
            ? $resource::reportingQueryScopes()
            : [];

        if (preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/', $scope) !== 1
            || ! in_array($scope, $declaredScopes, true)
            || ! method_exists($resource, 'scope'.Str::studly($scope))) {
            throw new InvalidArgumentException('Reporting query scopes must be explicitly allowlisted no-argument Eloquent scopes.');
        }

        $query->{$scope}();
    }

    /** @param Builder<\Aura\Base\Resource> $query */
    private function applyRange(Builder $query, Resource $resource, ?DateRange $range): void
    {
        if ($range === null) {
            return;
        }

        $query->where($resource->qualifyColumn('created_at'), '>=', $this->utc($range->start))
            ->where($resource->qualifyColumn('created_at'), '<', $this->utc($range->end));
    }

    /** @return Builder<\Aura\Base\Resource> */
    private function authorizedQuery(Resource $resource): Builder
    {
        $query = $resource->newQuery();

        if (method_exists($resource, 'indexQuery')) {
            $query = $resource->indexQuery($query);
        }

        return $query;
    }

    private function average(string $sum, int $count): string
    {
        $scaled = $this->integer($sum);
        $negative = $scaled < 0;
        $absolute = abs($scaled);
        $rounded = intdiv($absolute, $count);

        if (($absolute % $count) * 2 >= $count) {
            $rounded++;
        }

        return $this->decimal($negative ? -$rounded : $rounded);
    }

    /** @return list<array{0: string, 1: string, 2: string}> */
    private function bucketBoundaries(DateRange $range, ?DateBucket $bucket, string $timezone): array
    {
        if ($bucket === null || ! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException('Reporting buckets require an approved bucket and valid IANA timezone.');
        }

        $cursor = CarbonImmutable::instance($range->start)->setTimezone($timezone);
        $end = CarbonImmutable::instance($range->end)->setTimezone($timezone);
        $cursor = match ($bucket) {
            DateBucket::Day => $cursor->startOfDay(),
            DateBucket::Week => $cursor->startOfWeek(),
            DateBucket::Month => $cursor->startOfMonth(),
            DateBucket::Quarter => $cursor->firstOfQuarter(),
            DateBucket::Year => $cursor->startOfYear(),
        };
        $boundaries = [];

        while ($cursor < $end) {
            $next = match ($bucket) {
                DateBucket::Day => $cursor->addDay(),
                DateBucket::Week => $cursor->addWeek(),
                DateBucket::Month => $cursor->addMonthNoOverflow(),
                DateBucket::Quarter => $cursor->addQuarter(),
                DateBucket::Year => $cursor->addYear(),
            };
            $key = match ($bucket) {
                DateBucket::Day => $cursor->format('Y-m-d'),
                DateBucket::Week => $cursor->format('o-\\WW'),
                DateBucket::Month => $cursor->format('Y-m'),
                DateBucket::Quarter => $cursor->format('Y').'-Q'.$cursor->quarter,
                DateBucket::Year => $cursor->format('Y'),
            };
            $boundaries[] = [$key, $cursor->utc()->toDateTimeString(), $next->utc()->toDateTimeString()];

            if (count($boundaries) > self::MAX_BUCKETS) {
                throw new InvalidArgumentException('Reporting bucket requests are limited to 400 points.');
            }

            $cursor = $next;
        }

        return $boundaries;
    }

    /**
     * @param  Builder<\Aura\Base\Resource>  $query
     * @return list<AggregatePoint>
     */
    private function bucketed(Builder $query, Resource $resource, AggregateDefinition $definition, ?string $metric): array
    {
        if ($definition->range === null) {
            throw new InvalidArgumentException('Reporting time buckets require a range.');
        }

        $boundaries = $this->bucketBoundaries($definition->range, $definition->bucket, $definition->timezone);
        $grammar = $query->getQuery()->getGrammar();
        $timestamp = $grammar->wrap($resource->qualifyColumn('created_at'));
        $cases = [];
        $bindings = [];

        foreach ($boundaries as [$key, $start, $end]) {
            $cases[] = "WHEN {$timestamp} >= ? AND {$timestamp} < ? THEN ?";
            array_push($bindings, $start, $end, $key);
        }

        $expression = 'CASE '.implode(' ', $cases).' END';
        $rows = $this->aggregateSelect($query, $definition->operation, $metric)
            ->selectRaw("{$expression} as bucket_key", $bindings)
            ->groupBy('bucket_key')
            ->orderBy('bucket_key')
            ->toBase()
            ->get();

        return $rows->map(fn (object $row): AggregatePoint => new AggregatePoint(
            key: (string) $row->bucket_key,
            label: (string) $row->bucket_key,
            value: $this->aggregateFromRow($row, $definition->operation),
            rowCount: (int) $row->row_count,
        ))->all();
    }

    private function decimal(string|int $scaled): string
    {
        $value = is_int($scaled) ? $scaled : $this->integer($scaled);
        $negative = $value < 0;
        $absolute = abs($value);

        return ($negative ? '-' : '').intdiv($absolute, 1_000_000).'.'.str_pad((string) ($absolute % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  Builder<\Aura\Base\Resource>  $query
     * @return list<AggregatePoint>
     */
    private function grouped(Builder $query, Resource $resource, AggregateDefinition $definition, ?string $metric): array
    {
        $field = $this->groupField($resource, $definition->groupBy);
        $fieldClass = $resource->fieldClassBySlug($definition->groupBy);
        $qualified = $resource->qualifyColumn($definition->groupBy);
        $wrapped = $query->getQuery()->getGrammar()->wrap($qualified);
        $rows = $this->aggregateSelect($query, $definition->operation, $metric)
            ->selectRaw("{$wrapped} as group_key")
            ->groupBy($qualified)
            ->orderByRaw("CASE WHEN {$wrapped} IS NULL THEN 1 ELSE 0 END")
            ->orderBy($qualified)
            ->limit(self::MAX_GROUPS + 1)
            ->toBase()
            ->get();

        if ($rows->count() > self::MAX_GROUPS) {
            throw new RuntimeException('Reporting groups exceed the 100 point limit.');
        }

        return $rows->map(function (object $row) use ($definition, $field, $fieldClass, $resource): AggregatePoint {
            $raw = $row->group_key;
            $key = $this->groupKey($raw, $fieldClass);
            $label = $raw === null
                ? 'Empty'
                : $this->groupLabel($raw, $field, $fieldClass, $resource);

            return new AggregatePoint($key, $label, $this->aggregateFromRow($row, $definition->operation), (int) $row->row_count);
        })->sort(function (AggregatePoint $left, AggregatePoint $right) use ($fieldClass): int {
            if ($left->key === null || $right->key === null) {
                return $left->key === $right->key ? 0 : ($left->key === null ? 1 : -1);
            }

            $leftKey = $fieldClass instanceof Number ? ExactDecimal::sortableKey($left->key) : $left->key;
            $rightKey = $fieldClass instanceof Number ? ExactDecimal::sortableKey($right->key) : $right->key;

            return strcmp($leftKey, $rightKey);
        })->values()->all();
    }

    /** @return array<string, mixed> */
    private function groupField(Resource $resource, ?string $slug): array
    {
        if ($slug === null || ! $resource->isTableField($slug)) {
            throw new InvalidArgumentException('Reporting groups must be declared physical scalar fields.');
        }

        $field = $resource->fieldBySlug($slug);
        $class = $resource->fieldClassBySlug($slug);

        if (! is_array($field) || ! ($class instanceof Boolean || $class instanceof Number || $class instanceof Select || $class instanceof Status || $class instanceof Text)) {
            throw new InvalidArgumentException('Reporting groups must be declared physical scalar fields.');
        }

        return $field;
    }

    private function groupKey(mixed $value, object $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($field instanceof Boolean) {
            return (bool) $value ? '1' : '0';
        }

        if ($field instanceof Number) {
            return $this->decimal((string) $this->scaledInteger($value));
        }

        return (string) $value;
    }

    /**
     * Resolve a human group label without the FieldValueContract presentation stack.
     * Prefer option catalogs (Select/Status/etc.), then field display(), then string cast.
     */
    private function groupLabel(mixed $raw, array $field, object $fieldClass, Resource $resource): string
    {
        if (method_exists($fieldClass, 'options')) {
            $options = $fieldClass->options($resource, $field);

            if ($options instanceof Collection) {
                $options = $options->all();
            }

            if (is_array($options)) {
                foreach ($options as $optionKey => $option) {
                    if (is_array($option) && array_key_exists('key', $option)) {
                        $key = $option['key'];
                        $value = $option['value'] ?? $option['label'] ?? $key;
                    } elseif (! is_int($optionKey)) {
                        $key = $optionKey;
                        $value = is_array($option) ? ($option['value'] ?? $option['label'] ?? $optionKey) : $option;
                    } else {
                        $key = $option;
                        $value = is_array($option) ? ($option['value'] ?? $option['label'] ?? $option) : $option;
                    }

                    if ((string) $key === (string) $raw) {
                        return (string) $value;
                    }
                }
            }
        }

        if (method_exists($fieldClass, 'display')) {
            $presented = $fieldClass->display($field, $raw, $resource);

            if ($presented instanceof Htmlable) {
                return html_entity_decode(strip_tags($presented->toHtml()), ENT_QUOTES, 'UTF-8');
            }

            if (is_scalar($presented) || $presented === null) {
                return html_entity_decode(strip_tags((string) $presented), ENT_QUOTES, 'UTF-8');
            }
        }

        return (string) $raw;
    }

    private function integer(string $value): int
    {
        if (preg_match('/^-?\d+$/', $value) !== 1) {
            throw new RuntimeException('Reporting aggregate overflowed its exact signed integer contract.');
        }

        $negative = str_starts_with($value, '-');
        $digits = ltrim($value, '-0');
        $digits = $digits === '' ? '0' : $digits;
        $maximum = $negative ? '9223372036854775808' : '9223372036854775807';

        if (strlen($digits) > strlen($maximum) || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new RuntimeException('Reporting aggregate overflowed its exact signed integer contract.');
        }

        return (int) $value;
    }

    /** @param Builder<\Aura\Base\Resource> $query */
    private function metricExpression(Builder $query, Resource $resource, AggregateDefinition $definition): ?string
    {
        if ($definition->operation === AggregateOperation::Count) {
            return null;
        }

        if ($definition->metric === null) {
            throw new InvalidArgumentException('Numeric reporting requires a declared metric field.');
        }

        $field = $resource->fieldBySlug($definition->metric);
        $fieldClass = $resource->fieldClassBySlug($definition->metric);

        if (! is_array($field) || ! $fieldClass instanceof Number) {
            throw new InvalidArgumentException('Reporting metrics must be declared Number fields.');
        }

        $configuration = method_exists($fieldClass, 'exactQueryConfiguration')
            ? $fieldClass->exactQueryConfiguration($field)
            : ['mode' => 'legacy', 'precision' => null, 'scale' => 0];

        // Null precision means integer/legacy Number without an explicit
        // DECIMAL(p, s) contract. Those are reporting-safe at scale 0 and must
        // not fall back to Number::DEFAULT_PRECISION (19), which is outside the
        // portable reporting envelope (precision ≤ 18, scale ≤ 6).
        $precision = $configuration['precision'] ?? 18;
        $scale = $configuration['scale'] ?? 0;

        if ($precision > 18 || $scale > 6) {
            throw new InvalidArgumentException('Reporting metrics require precision at most 18 and scale at most 6.');
        }

        if ($resource->isTableField($definition->metric)) {
            return $this->physicalScaledExpression($query, $resource->qualifyColumn($definition->metric));
        }

        // Meta projection dual-write/read is intentionally not ported. Widgets
        // continue to use their legacy meta CAST path for non-physical metrics.
        throw new InvalidArgumentException(
            config('aura.reporting.projection.reads_enabled', false)
                ? 'Meta-backed reporting metrics require the typed projection stack, which is not available in this build.'
                : 'Meta-backed reporting metrics require enabled typed projection reads.'
        );
    }

    private function physicalScaledExpression(Builder $query, string $qualifiedColumn): string
    {
        $connection = $query->getConnection();

        if (! $connection instanceof Connection) {
            throw new RuntimeException('Reporting requires a concrete database connection.');
        }

        $grammar = $query->getQuery()->getGrammar();
        $column = $grammar->wrap($qualifiedColumn);

        if ($connection->getDriverName() === 'sqlite') {
            $this->registerSqliteScaler($connection);

            return "aura_reporting_scaled({$column})";
        }

        return match ($connection->getDriverName()) {
            'pgsql' => "CAST({$column} * 1000000 AS BIGINT)",
            'mariadb', 'mysql' => "CAST({$column} * 1000000 AS SIGNED)",
            default => throw new RuntimeException('Reporting has no approved numeric expression for this database driver.'),
        };
    }

    private function registerSqliteScaler(Connection $connection): void
    {
        $connection->getPdo()->sqliteCreateFunction('aura_reporting_scaled', fn (mixed $value): ?int => $this->scaledInteger($value), 1, true);
    }

    private function resource(string $class): Resource
    {
        if (! is_a($class, Resource::class, true) || ! in_array($class, app(Aura::class)->getResources(), true)) {
            throw new InvalidArgumentException('Reporting resources must be registered Aura Resource classes.');
        }

        return new $class;
    }

    private function scaledInteger(mixed $value): ?int
    {
        if (! is_int($value) && ! is_string($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/\A([+-]?)(\d+)(?:\.(\d{1,6}))?\z/', $value, $matches) !== 1) {
            return null;
        }

        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = str_pad($matches[3] ?? '', 6, '0');
        $digits = ltrim($integer.$fraction, '0');
        $digits = $digits === '' ? '0' : $digits;
        $negative = $matches[1] === '-' && $digits !== '0';
        $maximum = $negative ? '9223372036854775808' : '9223372036854775807';

        if (strlen($digits) > strlen($maximum) || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            return null;
        }

        return (int) ($negative ? '-'.$digits : $digits);
    }

    private function utc(DateTimeInterface $value): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($value)->setTimezone(new \DateTimeZone('UTC'));
    }
}
