<?php

namespace TomShaw\ElectricGrid;

enum FilterType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case MultiSelect = 'multiselect';
    case Boolean = 'boolean';
    case TimePicker = 'timepicker';
    case DatePicker = 'datepicker';
    case DateTimePicker = 'datetimepicker';
}
