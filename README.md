# Electric Grid 🔌

A fast and efficient Livewire data table. A great choice for projects that require a data table solution.

![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/tomshaw/electricgrid/run-tests.yml?branch=master&style=flat-square&label=tests)
![issues](https://img.shields.io/github/issues/tomshaw/electricgrid?style=flat&logo=appveyor)
![forks](https://img.shields.io/github/forks/tomshaw/electricgrid?style=flat&logo=appveyor)
![stars](https://img.shields.io/github/stars/tomshaw/electricgrid?style=flat&logo=appveyor)
[![GitHub license](https://img.shields.io/github/license/tomshaw/electricgrid)](https://github.com/tomshaw/electricgrid/blob/master/LICENSE)

## Features

Electric Grid is a powerful Livewire data table package with the following features:

1. **Easy Installation**: The package provides a simple command for installation and updates.

2. **Customizable Columns**: You can add columns to your grid with various options like sortable, styleable, exportable, visible, and callable.

3. **Column Filters**: The package supports text, number, select, multiselect, boolean, timepicker, datepicker, and datetimepicker.

4. **Mass Actions**: It provides the capability to execute operations on multiple rows of data simultaneously.

5. **Table Exports**: Tables can be exported in various formats like xlsx, csv, pdf, and html.

6. **Search Input**: You can enable search functionality by specifying the columns you wish to search.

7. **Letter Search**: This feature allows you to search data based on specific letters in the specified columns.

8. **Inline Actions**: You can add inline actions to your data rows, which can be useful for adding quick actions like update or delete.

9. **Themes**: The package uses a single template at around two hundred `loc` making it super easy to theme.

10. **Testing**: Provides a command for running tests to ensure the package works as expected.


## Installation

You can install the package via composer:

```bash
composer require tomshaw/electricgrid
```

Run the installation command.

```
php artisan electricgrid:install
```

Use the following command when updating the package:

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

Populating your table is done by using the `builder` method and supports eager loading out of the box.

> The builder method must return an instance of `Illuminate\Database\Eloquent\Builder`.

```php
use Illuminate\Database\Eloquent\Builder;
use TomShaw\ElectricGrid\Component;
use App\Models\Order;

class OrdersTable extends Component
{
    public function builder(): Builder
    {
        return Order::with(['user' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->select('orders.id', 'orders.user_id', 'orders.status', 'orders.total', 'orders.invoiced', 'orders.created_at', 'orders.updated_at');
    }
}
```

### Adding Columns.

Use Columns to present data in your grid. Columns support being `sortable`, `styleable`, `exportable`, `visible`, `callable` and appear in the order created.

> Column helper methods accept a toggleable boolean. 

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
                ->exportable(false)
                ->visible(),

            Column::add('name', 'Customer Name')
                ->searchable()
                ->sortable()
                ->exportable()
                ->visible(),

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
                ->callback(fn (Order $order) => Carbon::parse($order->created_at)->format('Y-m-d H:i'))
                ->sortable()
                ->exportable(),
        ];
    }
}
```

### Column Filters.

Filters allow you to filter data displayed in the grid. 

> Available filters include `text`, `number`, `select`, `multiselect`, `boolean`, `timepicker`, `datepicker` and `datetimepicker`.

Filter should used with their corresponding MySQL field types.

- `text`: VARCHAR or TEXT
- `number`: INT, FLOAT, DOUBLE, or DECIMAL
- `select`: Any type, as long as the value is in the set of options
- `multiselect`: Typically normalized into a separate table due to the many-to-many relationship
- `boolean`: TINYINT(1), where 0 is false and 1 is true
- `timepicker`: TIME
- `datepicker`: DATE
- `datetimepicker`: DATETIME

> Data attributes can be added using the `addDataAttribute` helper method.

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

### Mass Actions.

Mass or bulk actions provide the capability to execute operations on multiple rows of data simultaneously. To group actions togther use the `group` and `groupBy` helper methods.

```php
use TomShaw\ElectricGrid\{Component, Column, Filter, Action};
use App\Enums\OrderStatus;

class OrdersTable extends Component
{
    public bool $showCheckbox = true;

    public function actions(): array
    {
        return [
            Action::make('approved')
                ->label('Mark Approved')
                ->group('Status Options')
                ->callback(fn ($status, $selected) => $this->updateStatusHandler($status, $selected)),

            Action::make('completed')
                ->label('Mark Completed')
                ->group('Status Options')
                ->callback(fn ($status, $selected) => $this->updateStatusHandler($status, $selected)),

            Action::make('cancelled')
                ->label('Mark Canceled')
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

Out of the box tables can be exported in the following formats `xlsx`, `csv`, `pdf`, `html`. Export types are decided by `Extension-based format determination`. If you supply a file name of `SalesOrders.xlsx` an Excel spreadsheet will be generated.

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
                    Action::make('csv')->label('Export CSV')->export('SalesOrders.csv'),
                    Action::make('pdf')->label('Export PDF')->export('SalesOrders.pdf'),
                    Action::make('html')->label('Export HTML')->export('SalesOrders.html'),
                    Action::make('xlsx')->label('Export XLSX')->export('SalesOrders.xlsx'),
                ];
            }),
        ];
    }
}
```

### Search Input

Enable by adding the following property filled with the columns names you wish to search.

> Note: Array values are compared against your columns fields and must be visible.

```php
public array $searchTermColumns = ['title', 'description'];
```

### Letter Search

Enable by adding the following property filled with the columns names you wish to search.

> Note: Array values are compared against your columns fields and must be visible.

```php
public array $letterSearchColumns = ['name'];
```

### Inline Actions

Adding `Inline Actions` is done by utilizing the component setup method.

> Note: The following example uses `Route Model Binding`.

```php
protected function setup(): void
{
    $this->addInlineAction('Update'), 'admin.orders.update', ['order' => 'id']);
    $this->addInlineAction('Delete'), 'admin.orders.delete', ['order' => 'id']);
}
```

## Requirements

The package is compatible with Laravel 10 and PHP 8.1.

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
