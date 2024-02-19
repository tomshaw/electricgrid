<?php

namespace TomShaw\ElectricGrid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\{Component as BaseComponent, WithPagination};
use TomShaw\ElectricGrid\Exceptions\{DuplicateActionsHandler, RequiredMethodHandler};
use TomShaw\ElectricGrid\Traits\{WithGridActions, WithGridValidation};

class Component extends BaseComponent
{
    use WithGridActions;
    use WithGridValidation;
    use WithPagination;

    public string $theme = 'tailwind';

    public array $filter = [];

    public array $visibleColumns = [];

    public array $inlineActions = [];

    public bool $showCheckbox = true;

    public bool $showPagination = true;

    public bool $showTableInfo = true;

    public bool $showPerPage = true;

    public bool $showToggleColumns = true;

    public array $searchTermColumns = [];

    public array $letterSearchColumns = [];

    public string $searchTerm = '';

    public string $searchLetter = '';

    public int $perPage = 10;

    public array $perPageValues = [10, 20, 50, 100];

    public string $orderBy = 'id';

    public string $orderDir = 'ASC';

    public array $orderDirValues = ['ASC' => 'Ascending', 'DESC' => 'Descending'];

    public bool $checkboxAll = false;

    public array $checkboxValues = [];

    public string $checkboxField = 'id';

    public array $hiddenColumns = [];

    public function mount()
    {
        $this->setup();

        $this->visibleColumns = collect($this->columns)->filter->visible->pluck('field')->toArray();

        $this->searchTermColumns = array_intersect($this->searchTermColumns, $this->visibleColumns);

        $this->letterSearchColumns = array_intersect($this->letterSearchColumns, $this->visibleColumns);

        $this->validateColumns();
    }

    protected function setup(): void
    {
    }

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
        $colspan = count($this->visibleColumns);

        if ($this->showCheckbox) {
            $colspan++;
        }

        if (count($this->inlineActions)) {
            $colspan++;
        }

        return $colspan;
    }

    public function addInlineAction($text, $routeName, $routeParams = []): void
    {
        $this->inlineActions[] = [
            'text' => $text,
            'route' => $routeName,
            'params' => $routeParams,
        ];
    }

    public function handleCheckAll(bool $checked): void
    {
        $this->checkboxAll = $checked;

        $this->checkboxValues = [];
        if ($this->checkboxAll) {
            $this->builder()->each(fn ($model) => $this->checkboxValues[] = $model->{$this->checkboxField});
        }
    }

    public function handleSortOrder($field, $sortable): void
    {
        if (! $sortable) {
            return;
        }

        $this->resetPage();

        $this->orderDir = $this->orderDir === 'ASC' ? 'DESC' : 'ASC';

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

        $dataSource->filter($this->filter);

        $dataSource->orderBy($this->orderBy, $this->orderDir);

        $paginator = $dataSource->paginate($this->perPage);

        $paginator = $dataSource->transform($paginator, $this->columns);

        $page = new \stdClass();
        $page->firstItem = $paginator->firstItem();
        $page->lastItem = $paginator->lastItem();
        $page->total = $paginator->total();

        return view('electricgrid::'.$this->theme.'.table', [
            'data' => $paginator,
            'page' => $page,
        ]);
    }
}
