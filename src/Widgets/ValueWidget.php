<?php

namespace Aura\Base\Widgets;

use Aura\Base\Aura;
use Aura\Base\Fields\Number;
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

class ValueWidget extends Widget
{
    public $end;

    #[Locked]
    public $method = 'count';

    public $start;

    #[Locked]
    public $widget;

    public function getValue(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end): int|float|string|null
    {
        $column = optional($this->widget)['column'];

        // Never let an unknown/tampered column reach the raw-SQL identifier path.
        if ($column && ! $this->isSafeColumn($column)) {
            $column = null;
        }

        if ($this->canUseAggregateEngine($column)) {
            $operation = $this->aggregateOperation();

            return $this->scalarFromAggregate(app(AggregateEngine::class)->run(new AggregateDefinition(
                resource: $this->model::class,
                operation: $operation,
                metric: $operation === AggregateOperation::Count ? null : $column,
                range: new DateRange($start, $end),
                queryScope: is_string($this->widget['queryScope'] ?? null) ? $this->widget['queryScope'] : null,
            ))->value);
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

            $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

            return [
                'current' => $this->format($current),
                'previous' => $this->format($previous),
                'change' => $this->format($change),
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
        return view('aura::components.widgets.value');
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
     * AggregateEngine requires a registered Resource and physical metrics (or count).
     * Meta-backed metrics keep the legacy CAST path until projection reads exist.
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

        return $this->model->isTableField($column)
            && $this->model->fieldClassBySlug($column) instanceof Number;
    }

    protected function legacyValue(Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string $end, ?string $column): int|float|string|null
    {
        $isMetaColumn = $column && $this->model->isMetaField($column);

        $posts = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        // Apply the query scope only if it maps to a real Eloquent scope on the model.
        $queryScope = optional($this->widget)['queryScope'];
        if ($queryScope && method_exists($this->model, 'scope'.ucfirst($queryScope))) {
            $posts->{$queryScope}();
        }

        if ($isMetaColumn) {
            $posts->select($this->model->getTable().'.*', DB::raw("CAST(meta.value as SIGNED) as $column"))
                ->leftJoin('meta', function ($join) use ($column) {
                    $join->on($this->model->getQualifiedKeyName(), '=', 'meta.metable_id')
                        ->where('meta.key', '=', $column)
                        ->where('meta.metable_type', '=', $this->model->getMorphClass());
                });
        }

        return match ($this->method) {
            'avg' => $posts->avg($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'sum' => $posts->sum($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'min' => $posts->min($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'max' => $posts->max($isMetaColumn ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            default => $posts->count(),
        };
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
