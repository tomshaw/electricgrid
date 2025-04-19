<?php

namespace TomShaw\ElectricGrid;

use Illuminate\Database\Eloquent\Builder;
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

    public array $searchTermColumns = [];

    public array $letterSearchColumns = [];

    public array $computedColumns = [];

    public string $searchTerm = '';

    public string $searchLetter = '';

    public int $perPage = 15;

    public array $perPageValues = [10, 15, 20, 50, 75, 100];

    public string $orderBy = 'id';

    public string $orderDir = self::ORDER_ASC;

    public array $orderDirValues = [];

    public bool $checkboxAll = false;

    public array $checkboxValues = [];

    public string $checkboxField = 'id';

    public array $hiddenColumns = [];

    const ORDER_ASC = 'ASC';

    const ORDER_DESC = 'DESC';

    public function mount()
    {
        $this->setup();

        $this->orderDirValues = $this->getOrderDirValues();
    }

    protected function setup(): void {}

    public function builder(): Builder
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

    public function getBuilderProperty(): Builder
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

        $this->filter = array_merge($this->filter, ['search_term' => array_fill_keys($this->searchTermColumns, $this->searchTerm)]);
    }

    public function updatedSearchLetter(): void
    {
        $this->resetPage();
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
        $visibleColumns = collect($this->columns)->filter->visible->pluck('field')->toArray();

        $colspan = count($visibleColumns);

        if ($this->showCheckbox) {
            $colspan++;
        }

        return $colspan;
    }

    public function getOrderDirValues(): array
    {
        return [
            self::ORDER_ASC => 'Ascending',
            self::ORDER_DESC => 'Descending',
        ];
    }

    public function toggleOrderDirection(): void
    {
        $this->orderDir = $this->orderDir === self::ORDER_ASC ? self::ORDER_DESC : self::ORDER_ASC;
    }

    public function handleCheckAll(bool $checked): void
    {
        $this->checkboxAll = $checked;

        $this->checkboxValues = $checked
            ? $this->builder()->pluck($this->checkboxField)->all()
            : [];
    }

    public function handleSortOrder($field, $sortable): void
    {
        if (! $sortable) {
            return;
        }

        $this->resetPage();

        $this->toggleOrderDirection();

        $this->orderBy = $field;
    }

    public function handleSelectedLetter($selectedLetter): void
    {
        if ($this->searchLetter === $selectedLetter) {
            $this->searchLetter = '';
            $this->filter = array_diff_key($this->filter, ['search_letter' => '']);
        } else {
            $this->searchLetter = $selectedLetter;
            $this->filter = array_merge($this->filter, ['search_letter' => array_fill_keys($this->letterSearchColumns, $selectedLetter)]);
        }
    }

    public function handleToggleColumns(string $field): void
    {
        if (in_array($field, $this->hiddenColumns)) {
            $this->hiddenColumns = array_diff($this->hiddenColumns, [$field]);
        } else {
            $this->hiddenColumns[] = $field;
        }
    }

    public function render(): View
    {
        $dataSource = DataSource::make($this->builder);

        $dataSource->addComputedColumns($this->computedColumns);

        $dataSource->filter($this->filter);

        $dataSource->orderBy($this->orderBy, $this->orderDir);

        $paginator = $dataSource->paginate($this->perPage);

        $paginator = $dataSource->transform($paginator, $this->columns);

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
