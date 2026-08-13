<?php

namespace Aura\Base\Widgets;

use Aura\Base\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateEngine;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\DateRange;
use Aura\Base\Resource;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class Pie extends Widget
{
    public $end;

    #[Locked]
    public $method = 'count';

    public $start;

    #[Locked]
    public $widget;

    public function getValue(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end): array
    {
        $column = optional($this->widget)['column'];

        // Never let an unknown/tampered column reach the raw-SQL identifier path.
        if ($column && ! $this->isSafeColumn($column)) {
            $column = null;
        }

        if ($this->canUseAggregateEngine($column)) {
            $operation = $this->aggregateOperation();

            if (! $column) {
                return ['Total' => $this->scalarFromAggregate(app(AggregateEngine::class)->run(new AggregateDefinition(
                    resource: $this->model::class,
                    operation: AggregateOperation::Count,
                    range: new DateRange($start, $end),
                    queryScope: is_string($this->widget['queryScope'] ?? null) ? $this->widget['queryScope'] : null,
                ))->value)];
            }

            return collect(app(AggregateEngine::class)->run(new AggregateDefinition(
                resource: $this->model::class,
                operation: $operation,
                metric: $operation === AggregateOperation::Count ? null : $column,
                groupBy: $column,
                range: new DateRange($start, $end),
                queryScope: is_string($this->widget['queryScope'] ?? null) ? $this->widget['queryScope'] : null,
            ))->points)
                ->mapWithKeys(fn ($point): array => [$point->label => $this->scalarFromAggregate($point->value)])
                ->toArray();
        }

        return $this->legacyValue($start, $end, $column);
    }

    public function getValuesProperty(): array
    {
        $currentStart = $this->start instanceof Carbon ? $this->start : Carbon::parse($this->start);
        $currentEnd = $this->end instanceof Carbon ? $this->end : Carbon::parse($this->end);

        // Calculate the duration between start and end dates
        $duration = $currentStart->diffInDays($currentEnd);

        // Calculate previousStart and previousEnd based on the duration
        $previousStart = $currentStart->copy()->subDays($duration);
        $previousEnd = $currentStart;

        return cache()->remember($this->getCacheKeyProperty(), $this->getCacheDurationProperty(), function () use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
            $current = $this->getValue($currentStart, $currentEnd);
            $previous = $this->getValue($previousStart, $previousEnd);

            // $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

            return [
                'current' => $current,
                'previous' => $previous,
                // 'change' => $change,
            ];
        });
    }

    public function mount(): void
    {
        if (optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render(): View
    {
        return view('aura::components.widgets.pie');
    }

    #[On('dateFilterUpdated')]
    public function updateDateRange(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end): void
    {
        $this->start = $start;
        $this->end = $end;
    }

    protected function aggregateOperation(): AggregateOperation
    {
        return match ($this->method) {
            'avg' => AggregateOperation::Average,
            'sum' => AggregateOperation::Sum,
            'min' => AggregateOperation::Minimum,
            'max' => AggregateOperation::Maximum,
            default => AggregateOperation::Count,
        };
    }

    /**
     * Physical group-by (or plain count) through AggregateEngine when the resource is registered.
     * Meta/group-by-metric columns keep the legacy CAST path.
     */
    protected function canUseAggregateEngine(?string $column): bool
    {
        if (! $this->model instanceof Resource) {
            return false;
        }

        if (! in_array($this->model::class, app(Aura::class)->getResources(), true)) {
            return false;
        }

        if ($column === null) {
            return true;
        }

        // Group-by path only supports physical scalar group fields with count (or physical Number metrics).
        if (! $this->model->isTableField($column)) {
            return false;
        }

        $operation = $this->aggregateOperation();

        if ($operation === AggregateOperation::Count) {
            return $this->isEngineGroupField($column);
        }

        return $this->model->fieldClassBySlug($column) instanceof Number;
    }

    protected function isEngineGroupField(string $column): bool
    {
        $fieldClass = $this->model->fieldClassBySlug($column);

        return $fieldClass instanceof Boolean
            || $fieldClass instanceof Number
            || $fieldClass instanceof Select
            || $fieldClass instanceof Status
            || $fieldClass instanceof Text;
    }

    protected function legacyValue(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end, ?string $column): array
    {
        $table = $this->model->getTable();

        $posts = $this->model->query()
            ->where($table.'.created_at', '>=', $start)
            ->where($table.'.created_at', '<', $end);

        if (! $column) {
            return ['Total' => $posts->count()];
        }

        if ($column && $this->model->isMetaField($column)) {
            $posts->leftJoin('meta', function ($join) use ($column) {
                $join->on($this->model->getQualifiedKeyName(), '=', 'meta.metable_id')
                    ->where('meta.key', '=', $column)
                    ->where('meta.metable_type', '=', $this->model->getMorphClass());
            });

            $aggregateExpression = 'CAST(meta.value as SIGNED)';
            $labelExpression = 'meta.value';
        } else {
            $aggregateExpression = $table.'.'.$column;
            $labelExpression = $table.'.'.$column;
        }

        $method = in_array($this->method, ['avg', 'sum', 'min', 'max'], true) ? strtoupper($this->method) : 'COUNT';
        $aggregateSelect = $method === 'COUNT' ? 'COUNT(*)' : $method.'('.$aggregateExpression.')';

        return $posts->selectRaw($labelExpression.' as label, '.$aggregateSelect.' as aggregate')
            ->groupBy(DB::raw($labelExpression))
            ->pluck('aggregate', 'label')
            ->mapWithKeys(fn ($value, $label) => [(string) ($label ?: 'Empty') => $value])
            ->toArray();
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
