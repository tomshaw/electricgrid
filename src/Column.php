<?php

namespace TomShaw\ElectricGrid;

class Column
{
    public bool $searchable = false;

    public bool $sortable = false;

    public bool $exportable = false;

    public bool $visible = false;

    public string $styles = '';

    public ?\Closure $closure = null;

    public function __construct(
        public string $field,
        public string $title,
    ) {
    }

    public static function add(string $field, string $title): self
    {
        return new static($field, $title);
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

    public function visible(bool $visible = true): Column
    {
        $this->visible = $visible;

        return $this;
    }

    public function stylable(string $styles): Column
    {
        $this->styles = $styles;

        return $this;
    }

    public function callback(\Closure $closure): Column
    {
        $this->closure = $closure;

        return $this;
    }
}
