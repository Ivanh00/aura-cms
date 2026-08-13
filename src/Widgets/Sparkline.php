<?php

namespace Aura\Base\Widgets;

use Aura\Base\Aura;
use Aura\Base\Fields\Number;
use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateEngine;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\DateBucket;
use Aura\Base\Reporting\DateRange;
use Aura\Base\Resource;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class Sparkline extends Widget
{
    public $end;

    #[Locked]
    public $method = 'area';

    public $start;

    #[Locked]
    public $widget;

    public function getCarbonDate(Carbon|DateTimeInterface|string $date): Carbon
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    public function getValue(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end): Collection
    {
        $column = optional($this->widget)['column'];

        // Never let an unknown/tampered column reach the meta-join / raw-SQL path.
        if ($column && ! $this->isSafeColumn($column)) {
            $column = null;
        }

        if ($this->canUseAggregateEngine($column)) {
            $operation = $this->aggregateOperation();
            $result = app(AggregateEngine::class)->run(new AggregateDefinition(
                resource: $this->model::class,
                operation: $operation,
                metric: $operation === AggregateOperation::Count ? null : $column,
                range: new DateRange($start, $end),
                bucket: DateBucket::Day,
                timezone: is_string($this->widget['timezone'] ?? null) ? $this->widget['timezone'] : config('app.timezone', 'UTC'),
                queryScope: is_string($this->widget['queryScope'] ?? null) ? $this->widget['queryScope'] : null,
            ));
            $postsByDate = collect($result->points)
                ->mapWithKeys(fn ($point): array => [$point->key => $this->scalarFromAggregate($point->value) ?? 0])
                ->all();

            $dateRange = [];
            for ($date = Carbon::parse($start); $date->lte($end); $date->addDay()) {
                $dateRange[$date->format('Y-m-d')] = 0;
            }

            return collect($dateRange)->merge($postsByDate);
        }

        return $this->legacyValue($start, $end, $column);
    }

    public function getValuesProperty(): array
    {
        $currentStart = $this->getCarbonDate($this->start ?? now()->subDays(30)->startOfDay())->copy()->addDay();
        $currentEnd = $this->getCarbonDate($this->end ?? now()->endOfDay());
        $diff = round($currentStart->diffInDays($currentEnd));

        $previousStart = $currentStart->copy()->subDays($diff + 1);
        $previousEnd = $currentStart->copy()->subDay();

        // Keep uncached evaluation for sparkline tests and dashboards that
        // omit widget slugs; ValueWidget may still use the cache key path.
        return [
            'current' => $this->getValue($currentStart, $currentEnd)->toArray(),
            'previous' => $this->getValue($previousStart, $previousEnd)->toArray(),
        ];
    }

    public function mount(): void
    {
        if (optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render(): View
    {
        return view('aura::components.widgets.sparkline-area');
    }

    #[On('dateFilterUpdated')]
    public function updateDateRange(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end): void
    {
        $this->start = $start;
        $this->end = $end;
    }

    protected function aggregateOperation(): AggregateOperation
    {
        // Sparkline historically used method for chart style (area/bar), not
        // aggregate verb. Only honor explicit numeric verbs; default to count.
        return match ($this->method) {
            'avg' => AggregateOperation::Average,
            'sum' => AggregateOperation::Sum,
            'min' => AggregateOperation::Minimum,
            'max' => AggregateOperation::Maximum,
            default => AggregateOperation::Count,
        };
    }

    protected function canUseAggregateEngine(?string $column): bool
    {
        if (! $this->model instanceof Resource) {
            return false;
        }

        if (! in_array($this->model::class, app(Aura::class)->getResources(), true)) {
            return false;
        }

        $operation = $this->aggregateOperation();

        if ($operation === AggregateOperation::Count) {
            return true;
        }

        return $column !== null
            && $this->model->isTableField($column)
            && $this->model->fieldClassBySlug($column) instanceof Number;
    }

    protected function legacyValue(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end, ?string $column): Collection
    {
        $start = $this->getCarbonDate($start);
        $end = $this->getCarbonDate($end);
        $createdAtColumn = $this->model->getTable().'.created_at';
        $method = $this->method;

        $query = $this->model->query()
            ->where($createdAtColumn, '>=', $start)
            ->where($createdAtColumn, '<', $end)
            ->groupBy(DB::raw('DATE('.$createdAtColumn.')'))
            ->select(DB::raw('DATE('.$createdAtColumn.') as date'));

        if ($column && $this->model->isMetaField($column)) {
            $query->leftJoin('meta', function ($join) use ($column) {
                $join->on($this->model->getQualifiedKeyName(), '=', 'meta.metable_id')
                    ->where('meta.key', '=', $column)
                    ->where('meta.metable_type', '=', $this->model->getMorphClass());
            });

            if (in_array($method, ['avg', 'sum', 'min', 'max'])) {
                $query->addSelect(DB::raw("{$method}(CAST(meta.value as SIGNED)) as count"));
            } else {
                $query->addSelect(DB::raw('COUNT(*) as count'));
            }
        } else {
            $query->addSelect(DB::raw('COUNT(*) as count'));
        }

        $postsByDate = $query->get()->pluck('count', 'date')->toArray();

        // Generate a date range between $start and $end
        $dateRange = [];
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dateRange[$date->format('Y-m-d')] = 0;
        }

        // Merge date range with the results from the query
        return collect($dateRange)->merge($postsByDate);
    }

    protected function scalarFromAggregate(int|string|null $value): int|float|null
    {
        if ($value === null || is_int($value)) {
            return $value;
        }

        if (preg_match('/^-?\d+\.0+$/', $value) === 1) {
            return (int) $value;
        }

        return (float) $value;
    }
}
