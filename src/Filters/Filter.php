<?php

namespace TomShaw\ElectricGrid\Filters;

class Filter
{
    /**
     * Create a new FilterNumber instance.
     */
    public static function number(string $column): FilterNumber
    {
        return new FilterNumber($column);
    }

    /**
     * Create a new FilterText instance.
     */
    public static function text(string $column): FilterText
    {
        return new FilterText($column);
    }

    /**
     * Create a new FilterSelect instance.
     */
    public static function select(string $column): FilterSelect
    {
        return new FilterSelect($column);
    }

    /**
     * Create a new FilterMultiSelect instance.
     */
    public static function multiselect(string $column): FilterMultiSelect
    {
        return new FilterMultiSelect($column);
    }

    /**
     * Create a new FilterTimePicker instance.
     */
    public static function timepicker(string $column): FilterTimePicker
    {
        return new FilterTimePicker($column);
    }

    /**
     * Create a new FilterDatePicker instance.
     */
    public static function datepicker(string $column): FilterDatePicker
    {
        return new FilterDatePicker($column);
    }

    /**
     * Create a new FilterDateTimePicker instance.
     */
    public static function datetimepicker(string $column): FilterDateTimePicker
    {
        return new FilterDateTimePicker($column);
    }

    /**
     * Create a new FilterBoolean instance.
     */
    public static function boolean(string $column): FilterBoolean
    {
        return new FilterBoolean($column);
    }
}
