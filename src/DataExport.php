<?php

namespace TomShaw\ElectricGrid;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{Exportable, FromCollection, ShouldAutoSize, WithColumnWidths, WithHeadings, WithStyles};
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithHeadings, WithStyles
{
    use Exportable;

    public $fileName = 'DataExport.xlsx';

    public $styles = [];

    public $columnWidths = [];

    public function __construct(
        public Collection $collection
    ) {
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return array_keys((array) $this->collection->first());
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setColumnWidths($columnWidths)
    {
        $this->columnWidths = $columnWidths;

        return $this;
    }

    public function getColumnWidths(): array
    {
        return $this->columnWidths;
    }

    public function columnWidths(): array
    {
        return $this->getColumnWidths();
    }

    public function setStyles($styles)
    {
        $this->styles = $styles;

        return $this;
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function styles(Worksheet $sheet)
    {
        return $this->getStyles();
    }

    public function download()
    {
        return Excel::download($this, $this->getFileName());
    }
}
