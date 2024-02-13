<?php

namespace TomShaw\ElectricGrid;

use InvalidArgumentException;

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
    ) {
    }

    public static function make(string $field, string $title): self
    {
        return new static($field, $title);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function group(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    public static function groupBy(string $group, $actions): mixed
    {
        if (! ($actions instanceof \Closure)) {
            throw new InvalidArgumentException('The $actions argument must be an instance of \Closure');
        }

        $collection = collect($actions());

        $collection->each(function ($item) use ($group) {
            $item->group = $group;
        });

        return $collection;
    }

    public function export(string $fileName): self
    {
        $this->fileName = $fileName;
        $this->isExport();

        return $this;
    }

    public function isExport(bool $isExport = true): self
    {
        $this->isExport = $isExport;

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
