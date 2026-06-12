<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

use Illuminate\Support\Collection;

/** @phpstan-consistent-constructor */
class Action
{
    public string $group = '';

    public string $fileName = '';

    public bool $isExport = false;

    /** @var array<string, array<string, mixed>> */
    public array $styles = [];

    /** @var array<string, float|int> */
    public array $columnWidths = [];

    public ?\Closure $callable = null;

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

    /**
     * @param  \Closure(): array<int, self>  $actions
     * @return Collection<int, self>
     */
    public static function groupBy(string $group, \Closure $actions): Collection
    {
        return collect($actions())->each(fn (self $item) => $item->group = $group);
    }

    public function export(string $fileName): self
    {
        $this->isExport = true;

        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>>  $styles
     */
    public function styles(array $styles): self
    {
        $this->styles = $styles;

        return $this;
    }

    /**
     * @param  array<string, float|int>  $columnWidths
     */
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
