<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Concerns;

use TomShaw\ElectricGrid\Column;

/**
 * Persists grid state (filters, search, ordering, paging, hidden columns) to the
 * session and restores it on mount, pruning any state that no longer maps to a
 * defined column.
 *
 * Relies on members declared by the host component:
 *
 * @property array<string, mixed> $filter
 * @property string $searchTerm
 * @property string $searchLetter
 * @property int $perPage
 * @property string $orderBy
 * @property string $orderDir
 * @property array<int, string> $hiddenColumns
 * @property bool $persistFilters
 *
 * @method array<int, Column> columns()
 * @method void resetPage()
 * @method void resetInfiniteScroll()
 */
trait SessionState
{
    protected function getSessionKey(): string
    {
        return 'electricgrid.'.static::class;
    }

    protected function loadSessionState(): void
    {
        if (! $this->persistFilters) {
            return;
        }

        $defaultOrderBy = $this->orderBy;

        $state = session($this->getSessionKey());

        if (! is_array($state)) {
            return;
        }

        $filter = is_array($state['filter'] ?? null) ? $state['filter'] : [];

        $this->filter = array_diff_key(
            array_filter($filter, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY),
            ['search_term' => null, 'search_letter' => null]
        );

        $this->searchTerm = is_string($state['searchTerm'] ?? null) ? $state['searchTerm'] : '';
        $this->searchLetter = is_string($state['searchLetter'] ?? null) ? $state['searchLetter'] : '';
        $this->perPage = is_int($state['perPage'] ?? null) ? $state['perPage'] : $this->perPage;
        $this->orderBy = is_string($state['orderBy'] ?? null) ? $state['orderBy'] : $this->orderBy;
        $this->orderDir = is_string($state['orderDir'] ?? null) ? $state['orderDir'] : $this->orderDir;

        $hiddenColumns = is_array($state['hiddenColumns'] ?? null) ? $state['hiddenColumns'] : [];
        $this->hiddenColumns = array_values(array_filter($hiddenColumns, fn ($column) => is_string($column)));

        $this->pruneStaleState($defaultOrderBy);
    }

    protected function pruneStaleState(string $defaultOrderBy): void
    {
        $validFields = array_values(array_map(
            fn (Column $column): string => $column->field,
            $this->columns()
        ));

        foreach ($this->filter as $type => $values) {
            if (! is_array($values)) {
                continue;
            }

            $pruned = $this->pruneFilterBranch($values, $validFields, '');

            if ($pruned === []) {
                unset($this->filter[$type]);
            } else {
                $this->filter[$type] = $pruned;
            }
        }

        if ($this->orderBy !== '' && ! in_array($this->orderBy, $validFields, true)) {
            $this->orderBy = $defaultOrderBy;
        }
    }

    /**
     * @param  array<array-key, mixed>  $branch
     * @param  array<int, string>  $validFields
     * @return array<array-key, mixed>
     */
    protected function pruneFilterBranch(array $branch, array $validFields, string $prefix): array
    {
        $kept = [];

        foreach ($branch as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            // Exact field match: keep the whole subtree (e.g. start/end of a range filter).
            if (in_array($path, $validFields, true)) {
                $kept[$key] = $value;

                continue;
            }

            // Ancestor of a valid dotted field (e.g. 'project' for 'project.name'): recurse.
            if (is_array($value) && $this->isFieldAncestor($path, $validFields)) {
                $child = $this->pruneFilterBranch($value, $validFields, $path);

                if ($child !== []) {
                    $kept[$key] = $child;
                }
            }
        }

        return $kept;
    }

    /**
     * @param  array<int, string>  $validFields
     */
    protected function isFieldAncestor(string $path, array $validFields): bool
    {
        $needle = $path.'.';

        foreach ($validFields as $field) {
            if (str_starts_with($field, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function saveSessionState(): void
    {
        if (! $this->persistFilters) {
            return;
        }

        session()->put($this->getSessionKey(), [
            'filter' => $this->filter,
            'searchTerm' => $this->searchTerm,
            'searchLetter' => $this->searchLetter,
            'perPage' => $this->perPage,
            'orderBy' => $this->orderBy,
            'orderDir' => $this->orderDir,
            'hiddenColumns' => $this->hiddenColumns,
        ]);
    }

    public function clearSessionState(): void
    {
        session()->forget($this->getSessionKey());

        $this->filter = [];
        $this->searchTerm = '';
        $this->searchLetter = '';
        $this->hiddenColumns = [];
        $this->resetPage();
        $this->resetInfiniteScroll();
    }
}
