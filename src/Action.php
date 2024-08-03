<?php

namespace TomShaw\ElectricGrid;

class Action
{
    public string $group = '';

    public string $fileName = '';

    public bool $isExport = false;

    public array $styles = [];

    public array $columnWidths = [];

    public \Closure $callable;

    public function __construct(
        public string $field,
        public string $label,
    ) {}

    public static function make(string $field, string $title): self
    {
        return new static($field, $title);
    }

    public function group(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    public static function groupBy(string $group, \Closure $actions): mixed
    {
        return collect($actions())->each(fn ($item) => $item->group = $group);
    }

    public function export(string $fileName): self
    {
        $this->isExport = true;

        $this->fileName = $fileName;

        return $this;
    }

    public function styles(array $styles): self
    {
        $this->styles = $styles;

        return $this;
    }

    public function columnWidths(array $columnWidths): self
    {
        $this->columnWidths = $columnWidths;

        return $this;
    }

    public function callback(\Closure $callable): self
    {
        $this->callable = $callable;

        return $this;
    }
}
