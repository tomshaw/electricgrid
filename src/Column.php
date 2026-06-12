<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

use Closure;

/** @phpstan-consistent-constructor */
class Column
{
    public protected(set) bool $visible = true;

    public protected(set) bool $searchable = false;

    public protected(set) bool $sortable = false;

    public protected(set) bool $exportable = false;

    public protected(set) bool $actionable = false;

    public protected(set) bool $summable = false;

    public protected(set) bool $averageable = false;

    public protected(set) string $styles = '';

    public protected(set) ?Closure $closure = null;

    public protected(set) ?Closure $exportClosure = null;

    public function __construct(
        public protected(set) string $field = '',
        public protected(set) string $title = '',
    ) {}

    public static function add(string $field, string $title): static
    {
        return new static($field, $title);
    }

    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function exportable(bool $exportable = true): static
    {
        $this->exportable = $exportable;

        return $this;
    }

    public function actionable(bool $actionable = true): static
    {
        $this->actionable = $actionable;

        return $this;
    }

    /**
     * @param  string|array<int, string>  $styles
     */
    public function styles(string|array $styles): static
    {
        $this->styles = is_array($styles) ? implode(' ', $styles) : $styles;

        return $this;
    }

    public function summable(bool $summable = true): static
    {
        $this->summable = $summable;

        return $this;
    }

    public function averageable(bool $averageable = true): static
    {
        $this->averageable = $averageable;

        return $this;
    }

    public function callback(Closure $closure): static
    {
        $this->closure = $closure;

        return $this;
    }

    public function exportCallback(Closure $closure): static
    {
        $this->exportClosure = $closure;

        return $this;
    }
}
