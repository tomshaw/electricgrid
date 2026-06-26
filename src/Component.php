<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

use Illuminate\Database\Eloquent\{Builder, Collection as DatabaseCollection, Model};
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\{Component as BaseComponent, WithPagination};
use TomShaw\ElectricGrid\Exceptions\{DuplicateActionsHandler, RequiredMethodHandler};
use TomShaw\ElectricGrid\Traits\GridActions;

/**
 * @property-read array{sums?: array<string, float>, averages?: array<string, float>} $columnAggregates
 */
class Component extends BaseComponent
{
    use GridActions;
    use WithPagination;

    public string $theme = 'tailwind';

    /** @var array<string, mixed> */
    public array $filter = [];

    public bool $showCheckbox = true;

    public bool $showPagination = true;

    public bool $showTableInfo = true;

    public bool $showPerPage = true;

    public bool $showToggleColumns = true;

    public bool $persistFilters = false;

    public bool $infiniteScroll = false;

    public int $loadedPages = 1;

    /** @var array<int, string> */
    public array $searchTermColumns = [];

    /** @var array<int, string> */
    public array $letterSearchColumns = [];

    /** @var array<int, string> */
    public array $computedColumns = [];

    public string $searchTerm = '';

    public string $searchLetter = '';

    public int $perPage = 15;

    /** @var array<int, int> */
    public array $perPageValues = [15, 30, 50, 100];

    public bool $showAllOption = true;

    public int $showAllThreshold = 1000;

    public string $orderBy = 'id';

    public string $orderDir = self::ORDER_ASC;

    /** @var array<string, string> */
    public array $orderDirValues = [];

    public bool $checkboxAll = false;

    /** @var array<int, mixed> */
    public array $checkboxValues = [];

    public string $checkboxField = 'id';

    /** @var array<int, string> */
    public array $hiddenColumns = [];

    public ?string $rowHoverColor = null;

    public ?string $rowHoverColorDark = null;

    public ?string $rowStripeOdd = null;

    public ?string $rowStripeEven = null;

    public ?string $rowStripeOddDark = null;

    public ?string $rowStripeEvenDark = null;

    public ?string $captionText = null;

    public string $captionSide = 'top';

    const ORDER_ASC = 'ASC';

    const ORDER_DESC = 'DESC';

    protected BuilderDataSource|CollectionDataSource|null $memoizedDataSource = null;

    public function mount(): void
    {
        $this->loadSessionState();

        $this->setup();

        $this->orderDirValues = $this->getOrderDirValues();
    }

    protected function setup(): void {}

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
        $validFields = array_flip(array_map(
            fn (Column $column): string => $column->field,
            $this->columns()
        ));

        foreach ($this->filter as $type => $values) {
            if (! is_array($values)) {
                continue;
            }

            $pruned = array_intersect_key($values, $validFields);

            if ($pruned === []) {
                unset($this->filter[$type]);
            } else {
                $this->filter[$type] = $pruned;
            }
        }

        if ($this->orderBy !== '' && ! isset($validFields[$this->orderBy])) {
            $this->orderBy = $defaultOrderBy;
        }
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

    public function updated(string $_property): void
    {
        if (in_array($_property, ['perPage', 'infiniteScroll'], true)) {
            $this->resetInfiniteScroll();
        }

        $this->saveSessionState();
    }

    public function loadMore(): void
    {
        if (! $this->infiniteScroll) {
            return;
        }

        $this->loadedPages++;
    }

    protected function resetInfiniteScroll(): void
    {
        $this->loadedPages = 1;
    }

    /**
     * Return an Eloquent Builder for database queries, or a DatabaseCollection, Collection, or array for in-memory data.
     *
     * @return Builder<covariant Model>|DatabaseCollection<int, covariant Model>|Collection<int, covariant mixed>|array<array-key, mixed>
     */
    public function builder(): Builder|DatabaseCollection|Collection|array
    {
        throw RequiredMethodHandler::make('builder');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        throw RequiredMethodHandler::make('columns');
    }

    /**
     * @return array<int, Filters\FilterBase>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * @return array<int, Action|Collection<int, Action>>
     */
    public function actions(): array
    {
        return [];
    }

    public function rowClick(): ?\Closure
    {
        return null;
    }

    /**
     * @return Builder<covariant Model>|DatabaseCollection<int, covariant Model>|Collection<int, covariant mixed>|array<array-key, mixed>
     */
    public function getBuilderProperty(): Builder|DatabaseCollection|Collection|array
    {
        return $this->builder();
    }

    /**
     * @return array<int, Column>
     */
    public function getColumnsProperty(): array
    {
        return $this->columns();
    }

    /**
     * @return array<int, Filters\FilterBase>
     */
    public function getFiltersProperty(): array
    {
        return $this->filters();
    }

    public function updatedSearchTerm(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function updatedSearchLetter(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    /**
     * @return array<int|string, array<int, Action>>
     */
    public function getActionsProperty(): array
    {
        /** @var Collection<int, Action> $items */
        $items = collect($this->actions())->flatten();

        $duplicates = $items->map(fn (Action $item) => $item->field)->duplicates();

        if ($duplicates->count()) {
            throw DuplicateActionsHandler::make($duplicates->all());
        }

        return $items
            ->groupBy(fn (Action $item) => $item->group, true)
            ->map(fn (Collection $group) => $group->all())
            ->all();
    }

    public function getColspanProperty(): int
    {
        $visibleColumns = collect($this->getColumnsProperty())
            ->filter(fn (Column $column) => $column->visible)
            ->reject(fn (Column $column) => in_array($column->field, $this->hiddenColumns));

        $colspan = $visibleColumns->count();

        if ($this->showCheckbox) {
            $colspan++;
        }

        return $colspan;
    }

    /**
     * @return array<string, float>
     */
    public function getColumnSumsProperty(): array
    {
        return $this->columnAggregates['sums'] ?? [];
    }

    /**
     * @return array<string, float>
     */
    public function getColumnAveragesProperty(): array
    {
        return $this->columnAggregates['averages'] ?? [];
    }

    /**
     * @return array{sums?: array<string, float>, averages?: array<string, float>}
     */
    public function getColumnAggregatesProperty(): array
    {
        $visibleColumns = collect($this->getColumnsProperty())
            ->filter(fn (Column $column) => $column->visible && ! in_array($column->field, $this->hiddenColumns));

        $summableColumns = $visibleColumns->filter(fn (Column $column) => $column->summable)->map(fn (Column $column) => $column->field);
        $averageableColumns = $visibleColumns->filter(fn (Column $column) => $column->averageable)->map(fn (Column $column) => $column->field);

        if ($summableColumns->isEmpty() && $averageableColumns->isEmpty()) {
            return [];
        }

        $dataSource = clone $this->dataSource();

        $aggregates = [];

        if ($summableColumns->isNotEmpty()) {
            $aggregates['sums'] = $summableColumns->mapWithKeys(fn (string $field) => [$field => $dataSource->sum($field)])->all();
        }

        if ($averageableColumns->isNotEmpty()) {
            $aggregates['averages'] = $averageableColumns->mapWithKeys(fn (string $field) => [$field => $dataSource->avg($field)])->all();
        }

        return $aggregates;
    }

    /**
     * @return array<string, string>
     */
    public function getOrderDirValues(): array
    {
        return [
            self::ORDER_ASC => 'Ascending',
            self::ORDER_DESC => 'Descending',
        ];
    }

    public function rowHover(?string $light = null, ?string $dark = null): static
    {
        $this->rowHoverColor = $light;
        $this->rowHoverColorDark = $dark;

        return $this;
    }

    public function rowStripes(?string $odd = null, ?string $even = null, ?string $oddDark = null, ?string $evenDark = null): static
    {
        $this->rowStripeOdd = $odd;
        $this->rowStripeEven = $even;
        $this->rowStripeOddDark = $oddDark;
        $this->rowStripeEvenDark = $evenDark;

        return $this;
    }

    public function caption(?string $text = null, string $side = 'top'): static
    {
        $this->captionText = $text;
        $this->captionSide = $side === 'bottom' ? 'bottom' : 'top';

        return $this;
    }

    public function wrapperStyle(): string
    {
        $vars = [];

        $properties = [
            '--eg-row-hover' => $this->rowHoverColor,
            '--eg-row-hover-dark' => $this->rowHoverColorDark,
            '--eg-row-odd' => $this->rowStripeOdd,
            '--eg-row-even' => $this->rowStripeEven,
            '--eg-row-odd-dark' => $this->rowStripeOddDark,
            '--eg-row-even-dark' => $this->rowStripeEvenDark,
        ];

        foreach ($properties as $name => $value) {
            $color = $this->sanitizeHexColor($value);
            if ($color !== null) {
                $vars[] = "{$name}: {$color}";
            }
        }

        return implode('; ', $vars);
    }

    protected function sanitizeHexColor(?string $color): ?string
    {
        if ($color === null) {
            return null;
        }

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color) === 1 ? $color : null;
    }

    public function toggleOrderDirection(): void
    {
        $this->orderDir = $this->orderDir === self::ORDER_ASC ? self::ORDER_DESC : self::ORDER_ASC;
    }

    public function handleCheckAll(bool $checked): void
    {
        $this->checkboxAll = $checked;

        if ($checked) {
            $builder = $this->builder();
            if (is_array($builder)) {
                $this->checkboxValues = collect($builder)->pluck($this->checkboxField)->values()->all();
            } else {
                $this->checkboxValues = $builder->pluck($this->checkboxField)->values()->all();
            }
        } else {
            $this->checkboxValues = [];
        }
    }

    public function handleSortOrder(string $field, bool|string $sortable): void
    {
        if (! $sortable) {
            return;
        }

        $this->resetPage();
        $this->resetInfiniteScroll();

        if ($this->orderBy === $field) {
            $this->toggleOrderDirection();
        } else {
            $this->orderDir = self::ORDER_ASC;
        }

        $this->orderBy = $field;
        $this->saveSessionState();
    }

    public function handleSelectedLetter(string $selectedLetter): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();

        $this->searchLetter = $this->searchLetter === $selectedLetter ? '' : $selectedLetter;

        $this->saveSessionState();
    }

    public function handleToggleColumns(string $field): void
    {
        if (in_array($field, $this->hiddenColumns)) {
            $this->hiddenColumns = array_values(array_diff($this->hiddenColumns, [$field]));
        } else {
            $this->hiddenColumns[] = $field;
        }

        $this->saveSessionState();
    }

    /**
     * Build the filtered and searched data source once per request;
     * consumers clone it before mutating (ordering, pagination, aggregates).
     */
    protected function dataSource(): BuilderDataSource|CollectionDataSource
    {
        return $this->memoizedDataSource ??= $this->makeDataSource();
    }

    protected function makeDataSource(): BuilderDataSource|CollectionDataSource
    {
        $builder = $this->builder();

        $dataSource = $builder instanceof Builder
            ? BuilderDataSource::make($builder)
            : CollectionDataSource::make($builder);

        $dataSource->addComputedColumns($this->computedColumns);
        $dataSource->filter($this->filter);
        $dataSource->search($this->searchTerm, $this->searchTermColumns);
        $dataSource->searchLetter($this->searchLetter, $this->letterSearchColumns);

        return $dataSource;
    }

    protected function getTotalRecords(): int
    {
        return once(fn () => (clone $this->dataSource())->count());
    }

    public function shouldShowPerPageSelector(): bool
    {
        if (! $this->showPerPage) {
            return false;
        }

        $totalRecords = $this->getTotalRecords();

        // Hide if there are no records
        if ($totalRecords === 0 || $this->perPageValues === []) {
            return false;
        }

        // Hide if total records are less than or equal to the minimum per-page value
        $minPerPageValue = min($this->perPageValues);
        if ($totalRecords <= $minPerPageValue) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, int>
     */
    public function getAvailablePerPageValues(): array
    {
        $totalRecords = $this->getTotalRecords();

        // Filter out values that are greater than or equal to total records
        return array_filter($this->perPageValues, fn ($value) => $value < $totalRecords);
    }

    public function shouldShowAllOption(): bool
    {
        if (! $this->showAllOption) {
            return false;
        }

        $totalRecords = $this->getTotalRecords();

        // Don't show "All" if there are no records
        if ($totalRecords === 0) {
            return false;
        }

        // Don't show "All" if it would load too many records
        return $totalRecords <= $this->showAllThreshold;
    }

    public function render(): View
    {
        $dataSource = clone $this->dataSource();

        $dataSource->orderBy($this->orderBy, $this->orderDir);

        $effectivePerPage = $this->infiniteScroll
            ? $this->perPage * $this->loadedPages
            : $this->perPage;

        $paginator = $dataSource->paginate($effectivePerPage);

        $paginator = $dataSource->transform($paginator, $this->getColumnsProperty(), $this->rowClick());

        $page = new \stdClass;
        $page->firstItem = $paginator->firstItem();
        $page->lastItem = $paginator->lastItem();
        $page->total = $paginator->total();

        // @phpstan-ignore argument.type (the theme-based view name can only be resolved at runtime)
        return view('electricgrid::'.$this->theme.'.table', [
            'data' => $paginator,
            'page' => $page,
        ]);
    }
}
