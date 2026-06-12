<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

use Illuminate\Database\Eloquent\{Builder, Collection as DatabaseCollection};
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\{Component as BaseComponent, WithPagination};
use TomShaw\ElectricGrid\Exceptions\{DuplicateActionsHandler, RequiredMethodHandler};
use TomShaw\ElectricGrid\Traits\GridActions;

class Component extends BaseComponent
{
    use GridActions;
    use WithPagination;

    public string $theme = 'tailwind';

    public array $filter = [];

    public bool $showCheckbox = true;

    public bool $showPagination = true;

    public bool $showTableInfo = true;

    public bool $showPerPage = true;

    public bool $showToggleColumns = true;

    public bool $persistFilters = false;

    public bool $infiniteScroll = false;

    public int $loadedPages = 1;

    public array $searchTermColumns = [];

    public array $letterSearchColumns = [];

    public array $computedColumns = [];

    public string $searchTerm = '';

    public string $searchLetter = '';

    public int $perPage = 15;

    public array $perPageValues = [15, 30, 50, 100];

    public bool $showAllOption = true;

    public int $showAllThreshold = 1000;

    public string $orderBy = 'id';

    public string $orderDir = self::ORDER_ASC;

    public array $orderDirValues = [];

    public bool $checkboxAll = false;

    public array $checkboxValues = [];

    public string $checkboxField = 'id';

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

        $state = session($this->getSessionKey(), []);

        $this->filter = array_diff_key($state['filter'] ?? [], ['search_term' => null, 'search_letter' => null]);
        $this->searchTerm = $state['searchTerm'] ?? '';
        $this->searchLetter = $state['searchLetter'] ?? '';
        $this->perPage = $state['perPage'] ?? $this->perPage;
        $this->orderBy = $state['orderBy'] ?? $this->orderBy;
        $this->orderDir = $state['orderDir'] ?? $this->orderDir;
        $this->hiddenColumns = $state['hiddenColumns'] ?? [];
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
     */
    public function builder(): Builder|DatabaseCollection|Collection|array
    {
        throw RequiredMethodHandler::make('builder');
    }

    public function columns(): array
    {
        throw RequiredMethodHandler::make('columns');
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(): array
    {
        return [];
    }

    public function rowClick(): ?\Closure
    {
        return null;
    }

    public function getBuilderProperty(): Builder|DatabaseCollection|Collection|array
    {
        return $this->builder();
    }

    public function getColumnsProperty(): array
    {
        return $this->columns();
    }

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

    public function getActionsProperty(): array
    {
        $items = collect($this->actions())->flatten();

        $duplicates = $items->pluck('field')->duplicates();

        if ($duplicates->count()) {
            throw DuplicateActionsHandler::make($duplicates->toArray());
        }

        return collect($items)->groupBy(fn ($item) => $item->group, true)->toArray();
    }

    public function getColspanProperty(): int
    {
        $visibleColumns = collect($this->columns)
            ->filter->visible
            ->reject(fn ($column) => in_array($column->field, $this->hiddenColumns))
            ->pluck('field')
            ->toArray();

        $colspan = count($visibleColumns);

        if ($this->showCheckbox) {
            $colspan++;
        }

        return $colspan;
    }

    public function getColumnSumsProperty(): array
    {
        return $this->columnAggregates['sums'] ?? [];
    }

    public function getColumnAveragesProperty(): array
    {
        return $this->columnAggregates['averages'] ?? [];
    }

    public function getColumnAggregatesProperty(): array
    {
        $visibleColumns = collect($this->getColumnsProperty())
            ->filter(fn ($column) => $column->visible && ! in_array($column->field, $this->hiddenColumns));

        $summableColumns = $visibleColumns->filter(fn ($column) => $column->summable)->pluck('field');
        $averageableColumns = $visibleColumns->filter(fn ($column) => $column->averageable)->pluck('field');

        if ($summableColumns->isEmpty() && $averageableColumns->isEmpty()) {
            return [];
        }

        $dataSource = clone $this->dataSource();

        $aggregates = [];

        if ($summableColumns->isNotEmpty()) {
            $aggregates['sums'] = $summableColumns->mapWithKeys(fn ($field) => [$field => $dataSource->sum($field)])->toArray();
        }

        if ($averageableColumns->isNotEmpty()) {
            $aggregates['averages'] = $averageableColumns->mapWithKeys(fn ($field) => [$field => $dataSource->avg($field)])->toArray();
        }

        return $aggregates;
    }

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
                $this->checkboxValues = collect($builder)->pluck($this->checkboxField)->all();
            } else {
                $this->checkboxValues = $builder->pluck($this->checkboxField)->all();
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
        if ($totalRecords === 0) {
            return false;
        }

        // Hide if total records are less than or equal to the minimum per-page value
        $minPerPageValue = min($this->perPageValues);
        if ($totalRecords <= $minPerPageValue) {
            return false;
        }

        return true;
    }

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

        $paginator = $dataSource->transform($paginator, $this->columns, $this->rowClick());

        $page = new \stdClass;
        $page->firstItem = $paginator->firstItem();
        $page->lastItem = $paginator->lastItem();
        $page->total = $paginator->total();

        return view('electricgrid::'.$this->theme.'.table', [
            'data' => $paginator,
            'page' => $page,
        ]);
    }
}
