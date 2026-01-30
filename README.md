# Electric Grid 🔌

A powerful Livewire data table package. A great choice for projects that require a data table solution.

![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/tomshaw/electricgrid/run-tests.yml?branch=master&style=flat-square&label=tests)
![issues](https://img.shields.io/github/issues/tomshaw/electricgrid?style=flat&logo=appveyor)
![forks](https://img.shields.io/github/forks/tomshaw/electricgrid?style=flat&logo=appveyor)
![stars](https://img.shields.io/github/stars/tomshaw/electricgrid?style=flat&logo=appveyor)
[![GitHub license](https://img.shields.io/github/license/tomshaw/electricgrid)](https://github.com/tomshaw/electricgrid/blob/master/LICENSE)

> For a complete demonstration see the [Electric Grid Demo](https://github.com/tomshaw/electricgrid-demo) repository.

## Features

1. **Easy Installation**: Use a simple command for installation and package updates.

2. **Columns**: Add columns to your grid with various options including sortable, styleable, exportable and visible.

3. **Filters**: Supports text, number, select, multiselect, boolean, timepicker, datepicker, and datetimepicker.

4. **Mass Actions**: Provides the capability to execute operations on multiple rows of data simultaneously.

5. **Table Exports**: Tables can be exported in various formats including xlsx, csv, pdf, and html out of the box.

6. **Search Input**: You can enable search functionality by specifying the columns you wish to search.

7. **Letter Search**: This feature allows you to search data based on specific letters in the specified columns.

8. **Toggleable Columns**: Hide or show columns useful for focusing on the most relevant data.

9. **Smart Pagination**: Intelligent per-page controls that automatically hide when unnecessary and filter options based on dataset size.

10. **Themes**: Uses a single blade template under 230 lines of html making it super easy to theme.

11. **Testing**: Provides a command for running tests to ensure everything works as expected.

## Installation

You can install the package via composer:

```bash
composer require tomshaw/electricgrid
```

Run the installation command.

```
php artisan electricgrid:install
```

Run the update command for package updates:

```
php artisan electricgrid:update
```

Make sure to add the styles and scripts directives to your layout.

```html
@vite(['resources/css/app.css', 'resources/js/app.js'])

@electricgridStyles
@electricgridScripts
```

Add the component to your blade template where you plan to use it. 

> Here the namespace is `App\Livewire\Tables` and the component name is `OrdersTable`.

```html
<livewire:tables.orders-table />
```

## Usage

### The Builder Method.

Populating your table is done by using the `builder` method.

> The builder method must return an instance of `Illuminate\Database\Eloquent\Builder`.

```php
use Illuminate\Database\Eloquent\Builder;
use TomShaw\ElectricGrid\Component;
use App\Models\Order;

class OrdersTable extends Component
{
    public array $computedColumns = ['posts_count'];
    
    public function builder(): Builder
    {
        return User::with(['roles', 'profile'])->withCount('posts');
    }
}
```

### Adding Columns.

Use Columns to present data in your grid. 

```php
use TomShaw\ElectricGrid\{Component, Column};
use NumberFormatter;

class OrdersTable extends Component
{
    public function columns(): array
    {
        $numberFormat = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        return [
            Column::add('id', 'ID')
                ->sortable()
                ->stylable('text-center w-20')
                ->exportable(),

            Column::add('name', 'Customer')
                ->callback(function (Model $model) {
                    return view('livewire.tables.users-customer', ['model' => $model]);
                })
                ->exportCallback(fn (Model $model) => $model->name)
                ->searchable()
                ->sortable()
                ->exportable(),

            Column::add('status', 'Status')
                ->callback(function (Order $order) {
                    return OrderStatus::from($order->status)->name();
                })
                ->sortable()
                ->exportable(),

            Column::add('total', 'Total')
                ->callback(fn (Order $order) => $numberFormat->formatCurrency($order->total, 'USD'))
                ->searchable()
                ->sortable()
                ->exportable(),

            Column::add('invoiced', 'Invoiced')
                ->callback(fn (Order $order) => $order->invoiced ? 'Yes' : 'No')
                ->sortable()
                ->exportable(),

            Column::add('created_at', 'Created At')
                ->callback(fn (Order $order) => Carbon::parse($order->created_at)->format('F j, Y, g:i a'))
                ->sortable()
                ->exportable(),

            Column::add('updated_at', 'Updated At')
                ->callback(fn (Order $order) => Carbon::parse($order->updated_at)->format('F j, Y, g:i a'))
                ->sortable()
                ->exportable()
                ->visible(false),

        ];
    }
}
```

### Column Aggregates

Columns support aggregate calculations that display in the table footer.

#### Summable Columns

Add `.summable()` to display column totals:

```php
Column::add('total', 'Total')
    ->summable(),

Column::add('quantity', 'Quantity')
    ->summable(),
```

#### Averageable Columns

Add `.averageable()` to display column averages:

```php
Column::add('rating', 'Rating')
    ->averageable(),

Column::add('price', 'Price')
    ->averageable(),
```

#### Combined Aggregates

Columns can display both sum and average:

```php
Column::add('amount', 'Amount')
    ->summable()
    ->averageable(),
```

When both aggregates are present, they display as:
- **Sum: 1,500.00**
- **Avg: 150.00**

### Column Filters.

Filters allow you to filter data displayed in the grid. 

> Data attributes can be added to filters using the `addDataAttribute` method.

```php
use TomShaw\ElectricGrid\{Component, Column, Filter};
use App\Enums\OrderStatus;

class OrdersTable extends Component
{
    public function filters(): array
    {
        return [
            Filter::number('id')->placeholders('Min', 'Max'),
            Filter::text('name')->placeholder('Customer'),
            Filter::select('status')->options(OrderStatus::toOptions()),
            Filter::number('total')->placeholders('Min Total', 'Max Total'),
            Filter::boolean('invoiced')->labels('Yes', 'No'),
            Filter::datepicker('created_at')->addDataAttribute('format', 'H:i'),
            Filter::datetimepicker('updated_at'),
        ];
    }
}
```

#### Filter::number('id')

This creates a number filter for the 'id' field.

- `placeholders('Min', 'Max')`: Sets the placeholder text for the minimum and maximum input fields.

#### Filter::text('name')

This creates a text filter for the 'name' field.

- `placeholder('Customer')`: Sets the placeholder text for the input field.

#### Filter::select('status')

This creates a select filter for the 'status' field.

- `options(OrderStatus::toOptions())`: Sets the options for the select field using the `toOptions` method of the `OrderStatus` class.

#### Filter::number('total')

This creates a number filter for the 'total' field.

- `placeholders('Min Total', 'Max Total')`: Sets the placeholder text for the minimum and maximum input fields.

#### Filter::boolean('invoiced')

This creates a boolean filter for the 'invoiced' field.

- `labels('Yes', 'No')`: Sets the labels for the true and false values.

#### Filter::datepicker('created_at')

This creates a date picker filter for the 'created_at' field.

- `addDataAttribute('format', 'H:i')`: Sets a data attribute for the date picker. In this case, it's used to set the date format.

#### Filter::datetimepicker('updated_at')

This creates a datetime picker filter for the 'updated_at' field. The datetime picker uses the user's local date and time format.

#### Filter Column Types

Filters should be used with the appropriate database column types.

- `text`: VARCHAR or TEXT
- `number`: INT, FLOAT, DOUBLE, or DECIMAL
- `select`: Any type, as long as the value is a set of options
- `multiselect`: Any type, as long as the value is a set of options
- `boolean`: TINYINT(1), where 0 is false and 1 is true
- `timepicker`: TIME
- `datepicker`: DATE
- `datetimepicker`: DATETIME

### Mass Actions.

Mass or bulk actions provide the capability to execute operations on multiple rows of data simultaneously. 

> You can group actions together using the `group` and `groupBy` helper methods.

```php
use TomShaw\ElectricGrid\{Component, Column, Filter, Action};
use App\Enums\OrderStatus;

class OrdersTable extends Component
{
    public bool $showCheckbox = true;

    public function actions(): array
    {
        return [
            Action::make('approved', 'Mark Approved')
                ->group('Status Options')
                ->callback(fn ($status, $selected) => $this->updateStatusHandler($status, $selected)),

            Action::make('completed', 'Mark Completed')
                ->group('Status Options')
                ->callback(fn ($status, $selected) => $this->updateStatusHandler($status, $selected)),

            Action::make('cancelled', 'Mark Canceled')
                ->group('Status Options')
                ->callback(fn ($status, $selected) => $this->updateStatusHandler($status, $selected)),
        ];
    }

    public function updateStatusHandler(string $status, array $selectedItems)
    {
        $status = OrderStatus::fromName($status);

        foreach ($selectedItems as $index => $orderId) {
            event(new OrderStatusEvent($status->value, $orderId));
        }
    }
}
```

### Table Exports.

Tables can be exported in the following formats `xlsx`, `csv`, `pdf`, `html`. The type of export is decided by `Extension-based format determination`. If you supply a file name of `SalesOrders.xlsx` an Excel spreadsheet will be generated.

```php
use TomShaw\ElectricGrid\{Component, Column, Filter, Action};
use App\Enums\OrderStatus;

class OrdersTable extends Component
{
    public bool $showCheckbox = true;

    public function actions(): array
    {
        return [
            Action::groupBy('Export Options', function () {
                return [
                    Action::make('csv', 'Export CSV')->export('SalesOrders.csv'),
                    Action::make('pdf', 'Export PDF')->export('SalesOrders.pdf'),
                    Action::make('html', 'Export HTML')->export('SalesOrders.html'),
                    Action::make('xlsx', 'Export XLSX')->export('SalesOrders.xlsx'),
                ];
            }),
        ];
    }
}
```

## Custom Excel Exports

Excel exports can be customized with specific styles and column widths.

Uses the `styles` and `columnWidths` methods to apply custom styles and column widths to the Excel file.

The `styles` method returns an array of cell styles. The array keys are cell references and the values are arrays of style definitions. For example, `'1'  => ['font' => ['bold' => true]]` applies a bold font style to all cells in row 1, and `'B2' => ['font' => ['italic' => true]]` applies an italic font style to the cell at column B, row 2.

The `columnWidths` method returns an array of column widths. The array keys are column letters and the values are the widths of the columns. For example, `'A' => 20` sets the width of column A to 20.

Here's an example of how to define custom styles and column widths for Excel exports:

```php
    public function actions(): array
    {
        return [
            Action::groupBy('Export Options', function () {
                return [
                    Action::make('xlsx', 'Export XLSX')
                        ->export('SalesOrders.xlsx')
                        ->styles($this->exportStyles)
                        ->columnWidths($this->exportWidths),
                ];
            }),
        ];
    }

    public function getExportStylesProperty()
    {
        return [
            '1'  => ['font' => ['bold' => true]],
            'B2' => ['font' => ['italic' => true]],
            'C'  => ['font' => ['size' => 16]],
        ];
    }

    public function getExportWidthsProperty()
    {
        return [
            'A' => 20,
            'B' => 30,
        ];
    }
```

### Export Callbacks

When using HTML callbacks for display (e.g., Blade views for badges, buttons, or formatted content), you may want different output for exports. The `exportCallback` method allows you to specify how a column's data should be formatted when exported, separate from how it's displayed in the grid.

#### The Problem

If you use a callback that returns HTML for display:

```php
Column::add('status', 'Status')
    ->callback(fn (Order $order) => view('components.status-badge', ['status' => $order->status]))
    ->exportable()
```

The exported file would contain raw HTML like `<span class="badge badge-success">Active</span>` instead of just `Active`.

#### The Solution

Use `exportCallback` to define export-specific formatting:

```php
Column::add('status', 'Status')
    ->callback(fn (Order $order) => view('components.status-badge', ['status' => $order->status]))
    ->exportCallback(fn (Order $order) => $order->status)
    ->exportable()
```

Now the grid displays the styled badge, while exports contain the clean value.

#### Behavior

- **With `exportCallback` set**: Exports use the export callback
- **Without `exportCallback`**: Exports use the raw database value

This means you don't need to specify `exportCallback` if you just want the raw value - simply omit it:

```php
// Display: HTML badge | Export: raw database value
Column::add('status', 'Status')
    ->callback(fn (Order $order) => view('components.status-badge', ['status' => $order->status]))
    ->exportable()

// Display: formatted date | Export: custom date format
Column::add('created_at', 'Created At')
    ->callback(fn (Order $order) => view('components.date-display', ['date' => $order->created_at]))
    ->exportCallback(fn (Order $order) => $order->created_at->format('Y-m-d H:i:s'))
    ->exportable()

// Display: currency format | Export: same currency format
Column::add('total', 'Total')
    ->callback(fn (Order $order) => number_format($order->total, 2))
    ->exportCallback(fn (Order $order) => number_format($order->total, 2))
    ->exportable()
```

### Search Input

Enable by adding the following property filled with the columns names you wish to search.

```php
public array $searchTermColumns = ['title', 'description'];
```

### Letter Search

Enable by adding the following property filled with the columns names you wish to search.

```php
public array $letterSearchColumns = ['name'];
```

### Pagination & Per-Page Controls

Electric Grid includes intelligent per-page pagination controls that automatically adapt based on your dataset size.

#### Default Behavior

By default, the grid displays 15 records per page with the following options available:

```php
public int $perPage = 15;
public array $perPageValues = [15, 30, 50, 100];
```

The per-page selector automatically:
- **Hides when not needed** (no records or records ≤ minimum value)
- **Filters out irrelevant options** (removes values ≥ total records)
- **Shows only when useful** (requires more records than smallest per-page option)

#### Customizing Per-Page Options

Override the per-page values in your component:

```php
class OrdersTable extends Component
{
    public int $perPage = 25;
    public array $perPageValues = [25, 50, 100, 250];
}
```

#### "Show All" Option

The "All" option displays all records on a single page, but includes smart safeguards:

```php
public bool $showAllOption = true;        // Enable/disable "All" option
public int $showAllThreshold = 1000;      // Hide "All" if records exceed this
```

The "All" option automatically hides when:
- Total records exceed the `showAllThreshold`
- The `showAllOption` property is set to `false`
- There are no records in the dataset

Example with custom threshold:

```php
class OrdersTable extends Component
{
    public int $showAllThreshold = 500;  // Only show "All" for datasets under 500 records
}
```

#### Hiding the Per-Page Selector

Completely disable the per-page selector:

```php
public bool $showPerPage = false;
```

#### Global Configuration

You can set global defaults by publishing and modifying the config file:

```bash
php artisan vendor:publish --tag=electricgrid.config
```

Then edit `config/electricgrid.php`:

```php
'per_page' => [
    'default' => 15,
    'values' => [15, 30, 50, 100],
    'show_all' => true,
    'show_all_threshold' => 1000,
],
```

#### Session Persistence

Per-page settings are automatically saved to the session when `persistFilters` is enabled:

```php
class OrdersTable extends Component
{
    public bool $persistFilters = true;  // Remembers user's per-page selection
}
```

## Requirements

The package is compatible with Laravel 12+ and PHP 8.5.

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [License File](LICENSE) for more information.
