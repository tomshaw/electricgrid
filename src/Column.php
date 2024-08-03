<?php

namespace TomShaw\ElectricGrid;

class Column
{
    public bool $visible = true;

    public bool $searchable = false;

    public bool $sortable = false;

    public bool $exportable = false;

    public bool $actionable = false;

    public string $align = '';

    public string $style = '';

    public ?\Closure $closure = null;

    public function __construct(
        public string $field = '',
        public string $title = '',
    ) {}

    public static function add(string $field, string $title): self
    {
        return new static($field, $title);
    }

    public function visible(bool $visible = true): Column
    {
        $this->visible = $visible;

        return $this;
    }

    public function searchable(bool $searchable = true): Column
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function sortable(bool $sortable = true): Column
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function exportable(bool $exportable = true): Column
    {
        $this->exportable = $exportable;

        return $this;
    }

    public function actionable(bool $actionable = true): Column
    {
        $this->actionable = $actionable;

        return $this;
    }

    public function align(string $align): Column
    {
        $this->align = $align;

        return $this;
    }

    public function style(string $style): Column
    {
        $this->style = $style;

        return $this;
    }

    public function callback(\Closure $closure): Column
    {
        $this->closure = $closure;

        return $this;
    }
}
