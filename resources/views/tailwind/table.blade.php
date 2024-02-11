<div class="electricgrid">

  <div class="flex items-center justify-between w-full py-4">

    <div class="flex items-center justify-center gap-x-2">
      @if(count($searchTermColumns))
      <div class="flex items-center relative my-1">
        <span class="absolute ms-2">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor"></rect>
            <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor"></path>
          </svg>
        </span>
        <input type="text" wire:model.live.debounce.600ms="searchTerm" 
        class="form-input ring-0 outline-none w-[260px] text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md ps-10" placeholder="{{ __('Search') }}">
      </div>
      @endif
      <div wire:loading class="hidden">
        <div class="border-gray-300 h-6 w-6 animate-spin rounded-full border-2 border-t-gray-600"></div>
      </div>
    </div>

    <div class="flex items-center justify-center gap-x-2">
      @if(count($this->actions))
      <select wire:model.live="selectedAction" class="form-select ring-0 outline-none w-[260px] text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md">
        <option value="">{{ __('Choose...') }}</option>
        @foreach($this->actions as $group => $items)
        @if(!$group)
        @foreach($items as $item)
        <option value="{{$item->field}}">{{$item->label}}</option>
        @endforeach
        @else
        <optgroup label="{{$group}}">
          @foreach($items as $item)
          <option value="{{$item->field}}">{{$item->label}}</option>
          @endforeach
        </optgroup>
        @endif
        @endforeach
      </select>
      <button type="button" wire:click="handleSelectedAction" class="inline-flex items-center p-2.5 rounded-md bg-gray-800 dark:bg-gray-200 border border-transparent font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">Go</button>
      @endif
    </div>

  </div>

  <div class="w-full">
    <div class="overflow-x-auto">
      <table class="border-collapse table-auto w-full text-sm">
        <thead>

          <tr>
            <td class="border p-2" rowspan="1" colspan="{{$this->colspan}}">
              <div class="flex items-center justify-between">
                <p class="font-medium text-gray-600 leading-5">
                  <span>{!! __('Showing') !!}</span>
                  <span>{{ $page->firstItem }}</span>
                  <span>{!! __('to') !!}</span>
                  <span>{{ $page->lastItem }}</span>
                  <span>{!! __('of') !!}</span>
                  <span>{{ $page->total }}</span>
                  <span>{!! __('results') !!}.</span>
                </p>
                @if(count($letterSearchColumns))
                <div class="flex items-center justify-center gap-x-1">
                  @foreach(range('A', 'Z') as $value)
                  <span wire:click="handleSelectedLetter('{{ $value }}')" class="font-bold text-gray-600 hover:underline cursor-pointer {{ $value === $searchLetter ? 'text-blue-700 underline' : 'text-[#555]' }}">{{ $value }}</span>
                  @endforeach
                </div>
                @endif
              </div>
            </td>
          </tr>

          <tr class="headers">
            @if($showCheckbox)
            <th class="border px-2 py-3 w-[50px] min-w-[50px] max-w-[50px]" rowspan="1" colspan="1">
              <div class="flex items-center justify-center">
                <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600" wire.model="checkboxAll" wire:change="handleCheckAll($event.target.checked)">
              </div>
            </th>
            @endif
            @foreach($this->columns as $column)
            @if($column->visible)
            <th @class(['border px-2 py-3 text-left font-bold text-gray-600 tracking-wider whitespace-nowrap' . ($column->sortable ? ' cursor-pointer' : '')]) tabindex="0" rowspan="1" colspan="1" wire:click.live="handleSortOrder('{{$column->field}}', `{{$column->sortable}}`)">
              <div class="flex justify-between items-center">
                <span>{{$column->title}}</span>
                @if($column->sortable === true)
                <div @class(['flex flex-col items-center text-gray-200', '!text-gray-800'=> ($orderBy === $column->field)])>
                  @if ($orderDir === 'ASC')
                  <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                  </svg>
                  @else
                  <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                  @endif
                </div>
                @endif
              </div>
            </th>
            @endif
            @endforeach
            @if(count($this->inlineActions))
            <th class="border px-2 py-3 text-center font-bold text-gray-500 tracking-wider whitespace-nowrap" rowspan="1" colspan="1">Actions</th>
            @endif
          </tr>

          <tr class="filters">
            @if($showCheckbox)
            <td class="border p-2"></td>
            @endif
            @foreach($this->columns as $column)
            @if($column->visible)
            <td @class(['border p-2 align-top'])>
              @foreach ($this->filters as $key => $filter)
              @if($filter->column === $column->field)
              <div class="flex flex-col items-start justify-center" wire:key="filter-{{ $column->field }}-{{ $key }}">

                @if($filter->type('FilterText'))
                <input type="text" class="form-input w-full ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live.debounce.1s="filter.text.{{ $column->field }}" placeholder="{{ $filter->placeholder }}" {!! $filter->getDataAttributes() !!}>
                @endif

                @if($filter->type('FilterNumber'))
                <input type="number" class="form-input w-full min-w-[140px] ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md mb-2" wire:model.live.debounce.1s="filter.number.{{ $column->field }}.start" placeholder="{{ $filter->placeholders['min'] }}" {!! $filter->getDataAttributes() !!}>
                <input type="number" class="form-input w-full min-w-[140px] ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live.debounce.1s="filter.number.{{ $column->field }}.end" placeholder="{{ $filter->placeholders['max'] }}" {!! $filter->getDataAttributes() !!}>
                @endif

                @if($filter->type('FilterSelect'))
                <select class="form-select w-full min-w-[140px] ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live.debounce.1s="filter.select.{{ $column->field }}" {!! $filter->getDataAttributes() !!}>
                  <option value="-1">{{ __('All') }}</option>
                  @foreach($filter->options as $value => $label)
                  <option value="{{ $value }}">{{ $label }}</option>
                  @endforeach
                </select>
                @endif

                @if($filter->type('FilterMultiSelect'))
                <select class="form-select-multiple w-full min-w-[140px] ring-0 outline-none text-gray-500 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live.debounce.1s="filter.multiselect.{{ $column->field }}" {!! $filter->getDataAttributes() !!} multiple>
                  <option value="-1">{{ __('All') }}</option>
                  @foreach($filter->options as $value => $label)
                  <option value="{{ $value }}">{{ $label }}</option>
                  @endforeach
                </select>
                @endif

                @if($filter->type('FilterBoolean'))
                <select class="form-select w-full min-w-[140px] ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live.debounce.1s="filter.boolean.{{ $column->field }}" {!! $filter->getDataAttributes() !!}>
                  <option value="-1">{{ __('All') }}</option>
                  @foreach($filter->options as $value => $label)
                  <option value="{{ $value }}">{{ $label }}</option>
                  @endforeach
                </select>
                @endif

                @if($filter->type('FilterTimePicker'))
                <input type="time" class="form-input w-full ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md mb-2" min="{{$filter->startMin}}" max="{{$filter->startMax}}" step="{{$filter->startStep}}" wire:model.live.debounce.300ms="filter.timepicker.{{ $column->field }}.start" {!! $filter->getDataAttributes() !!}>
                <input type="time" class="form-input w-full ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" min="{{$filter->endMin}}" max="{{$filter->endMax}}" step="{{$filter->endStep}}" wire:model.live.debounce.300ms="filter.timepicker.{{ $column->field }}.end" {!! $filter->getDataAttributes() !!}>
                @endif

                @if($filter->type('FilterDatePicker'))
                <input type="date" class="form-input w-full min-w-[140px] ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md mb-2" wire:model.live.debounce.300ms="filter.datepicker.{{ $column->field }}.start" {!! $filter->getDataAttributes() !!}>
                <input type="date" class="form-input w-full ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live.debounce.300ms="filter.datepicker.{{ $column->field }}.end" {!! $filter->getDataAttributes() !!}>
                @endif

                @if($filter->type('FilterDateTimePicker'))
                <input type="datetime-local" class="form-input w-full min-w-[140px] ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md mb-2" wire:model.live.debounce.300ms="filter.datetimepicker.{{ $column->field }}.start" {!! $filter->getDataAttributes() !!}>
                <input type="datetime-local" class="form-input w-full ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live.debounce.300ms="filter.datetimepicker.{{ $column->field }}.end" {!! $filter->getDataAttributes() !!}>
                @endif

              </div>
              @endif
              @endforeach
            </td>
            @endif
            @endforeach
            @if(count($this->inlineActions))
            <td class="border p-2" colspan="1"></td>
            @endif
          </tr>

        </thead>
        <tbody>
          @foreach($data as $row)
          <tr wire:key="{{ $row->id }}">
            @if($showCheckbox)
            <td class="border p-2 w-4">
              <div class="flex items-center justify-center">
                <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600" wire:model.live="checkboxValues" value="{{ $row->{$checkboxField} }}">
              </div>
            </td>
            @endif
            @foreach($this->columns as $column)
            @php
            $field = $column->field;
            @endphp
            @if($column->visible)
            <td @class(['text-gray-600 border p-2', $column->styles])>
              {!! $row->$field !!}
            </td>
            @endif
            @endforeach
            @if(count($this->inlineActions))
            <td class="border p-2 text-left">
              <div class="flex items-center justify-center gap-x-2">
                @foreach($this->inlineActions as $item)
                <a href="{{ route($item['route'], array_map(fn($value) => $row->$value, $item['params'])) }}" class="inline-flex items-center px-2.5 py-1.5 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150" title="{{ $item['text'] }}">
                  {{ $item['text'] }}
                </a>
                @endforeach
              </div>
            </td>
            @endif
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="flex justify-between items-center py-4">

    @if($showPagination && method_exists($data, 'links'))
    {!! $data->links('electricgrid::tailwind.pagination') !!}
    @endif

    @if($showPerPage)
    <select class="form-select ring-0 outline-none text-gray-600 border border-gray-300 hover:border-gray-400 hover:ring-0 focus:ring-0 rounded-md" wire:model.live="perPage">
      <option value="0">{{ __('View All') }}</option>
      @foreach($perPageValues as $value)
      <option value="{{$value}}">{{ $value }}</option>
      @endforeach
    </select>
    @endif

  </div>

</div>