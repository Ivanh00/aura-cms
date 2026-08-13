<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Support\KanbanConfiguration;

/**
 * Trait to handle kanban board columns and preferences.
 */
trait Kanban
{
    /**
     * @var array<string, array{value: string, color: string|null, visible: bool}>
     */
    public array $kanbanStatuses = [];

    public function mountKanban(): void
    {
        $this->prepareKanban();
    }

    public function reorderKanbanColumns(array $newOrder): void
    {
        $this->reorderKanbanStatuses($newOrder);
    }

    public function reorderKanbanStatuses(array|string|int $statuses, ?int $position = null): void
    {
        $declared = $this->declaredKanbanStatuses();
        $requested = $this->requestedKanbanStatusOrder($statuses, $position, $declared);
        $orderedKeys = collect($requested)
            ->filter(fn (mixed $key): bool => is_string($key) || is_int($key))
            ->map(fn (string|int $key): string => (string) $key)
            ->filter(fn (string $key): bool => array_key_exists($key, $declared))
            ->unique()
            ->merge(array_keys($declared))
            ->unique();

        $this->kanbanStatuses = $orderedKeys
            ->mapWithKeys(fn (string $key): array => [
                $key => array_replace($declared[$key], [
                    'visible' => (bool) ($this->kanbanStatuses[$key]['visible'] ?? true),
                ]),
            ])
            ->all();

        $this->saveKanbanStatusesOrder();
    }

    public function updatedKanbanStatuses(): void
    {
        $this->kanbanStatuses = $this->sanitizeKanbanStatuses($this->kanbanStatuses);
        $this->saveKanbanStatusesOrder();
    }

    protected function applyKanbanQuery($query)
    {
        $kanbanQuery = $this->model->kanbanQuery($query);

        if ($kanbanQuery) {
            return $kanbanQuery;
        }

        return $query;
    }

    /**
     * @return array<string, array{value: string, color: string|null, visible: bool}>
     */
    protected function declaredKanbanStatuses(): array
    {
        $columns = $this->resolvedKanbanConfiguration()['columns'];

        if ($columns !== []) {
            return collect($columns)
                ->map(fn (array $column): array => array_replace($column, ['visible' => true]))
                ->all();
        }

        // Fallback: legacy status-field options when KanbanConfiguration has no columns.
        $field = $this->model->fieldBySlug('status');
        $options = is_array($field) ? ($field['options'] ?? []) : [];

        return collect($options)->mapWithKeys(function ($status) {
            if (! is_array($status) || ! array_key_exists('key', $status)) {
                return [];
            }

            return [$status['key'] => [
                'value' => (string) ($status['value'] ?? $status['key']),
                'color' => is_string($status['color'] ?? null) ? $status['color'] : null,
                'visible' => true,
            ]];
        })->all();
    }

    protected function initializeKanbanStatuses(): void
    {
        $this->kanbanStatuses = $this->declaredKanbanStatuses();

        $userPreferences = auth()->user()->getOption('kanban_statuses.'.$this->model()->getType());
        if (is_array($userPreferences)) {
            $this->kanbanStatuses = $this->sanitizeKanbanStatuses($userPreferences);
        }
    }

    /**
     * Also called from Table::rowsQuery(): trait mount hooks fire in trait
     * declaration order, so mountKanban() can run before mountSwitchView()
     * has set $currentView — and switchView() never re-initializes statuses.
     */
    protected function prepareKanban(): void
    {
        if ($this->currentView !== 'kanban') {
            return;
        }

        if (empty($this->kanbanStatuses)) {
            $this->initializeKanbanStatuses();
        }

        if (method_exists($this->model, 'kanbanPagination')) {
            $this->perPage = $this->model->kanbanPagination();
        }
    }

    /**
     * @param  array<string, array{value: string, color: string|null, visible: bool}>  $declared
     * @return array<int, string|int>
     */
    protected function requestedKanbanStatusOrder(array|string|int $statuses, ?int $position, array $declared): array
    {
        if (is_array($statuses)) {
            return $statuses;
        }

        if (
            ! is_int($position)
            || $position < 0
            || ! array_key_exists((string) $statuses, $declared)
        ) {
            abort(422, 'The Kanban column order is invalid.');
        }

        $status = (string) $statuses;
        $ordered = collect(array_keys($this->sanitizeKanbanStatuses($this->kanbanStatuses)))
            ->reject(fn (string $key): bool => $key === $status)
            ->values()
            ->all();

        array_splice($ordered, min($position, count($ordered)), 0, [$status]);

        return $ordered;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     valid: bool,
     *     group_field: string,
     *     columns: array<string, array{value: string, color: string|null}>,
     *     card_title: string,
     *     card_subtitle: string|null,
     *     order_by: array{field: string, direction: 'asc'|'desc'}|null,
     *     show_empty_columns: bool
     * }
     */
    protected function resolvedKanbanConfiguration(): array
    {
        return KanbanConfiguration::for($this->model);
    }

    protected function sanitizeKanbanStatuses(array $preferences): array
    {
        $declared = $this->declaredKanbanStatuses();

        if ($declared === []) {
            return [];
        }

        $orderedKeys = collect(array_keys($preferences))
            ->map(fn (string|int $key): string => (string) $key)
            ->filter(fn (string $key): bool => array_key_exists($key, $declared))
            ->unique()
            ->merge(array_keys($declared))
            ->unique();

        return $orderedKeys->mapWithKeys(function (string $key) use ($declared, $preferences): array {
            $preference = $preferences[$key] ?? [];

            return [$key => array_replace($declared[$key], [
                'visible' => is_array($preference) ? (bool) ($preference['visible'] ?? true) : true,
            ])];
        })->all();
    }

    protected function saveKanbanStatusesOrder(): void
    {
        $this->kanbanStatuses = $this->sanitizeKanbanStatuses($this->kanbanStatuses);
        auth()->user()->updateOption('kanban_statuses.'.$this->model()->getType(), $this->kanbanStatuses);
    }
}
