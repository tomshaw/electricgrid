<?php

namespace TomShaw\ElectricGrid\Filters;

class Filter
{
    /**
     * Create a new FilterNumber instance.
     *
     * @param  string  $column
     * @return FilterNumber
     */
    public static function number(string $column): FilterNumber
    {
        return new FilterNumber($column);
    }

    /**
     * Create a new FilterText instance.
     *
     * @param  string  $column
     * @return FilterText
     */
    public static function text(string $column): FilterText
    {
        return new FilterText($column);
    }

    /**
     * Create a new FilterSelect instance.
     *
     * @param  string  $column
     * @return FilterSelect
     */
    public static function select(string $column): FilterSelect
    {
        return new FilterSelect($column);
    }

    /**
     * Create a new FilterMultiSelect instance.
     *
     * @param  string  $column
     * @return FilterMultiSelect
     */
    public static function multiselect(string $column): FilterMultiSelect
    {
        return new FilterMultiSelect($column);
    }

    /**
     * Create a new FilterTimePicker instance.
     *
     * @param  string  $column
     * @return FilterTimePicker
     */
    public static function timepicker(string $column): FilterTimePicker
    {
        return new FilterTimePicker($column);
    }

    /**
     * Create a new FilterDatePicker instance.
     *
     * @param  string  $column
     * @return FilterDatePicker
     */
    public static function datepicker(string $column): FilterDatePicker
    {
        return new FilterDatePicker($column);
    }

    /**
     * Create a new FilterDateTimePicker instance.
     *
     * @param  string  $column
     * @return FilterDateTimePicker
     */
    public static function datetimepicker(string $column): FilterDateTimePicker
    {
        return new FilterDateTimePicker($column);
    }

    /**
     * Create a new FilterBoolean instance.
     *
     * @param  string  $column
     * @return FilterBoolean
     */
    public static function boolean(string $column): FilterBoolean
    {
        return new FilterBoolean($column);
    }
}
