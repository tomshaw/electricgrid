<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\{IOFactory, Spreadsheet};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, ResponseHeaderBag};

class DataExport
{
    public string $fileName = 'DataExport.xlsx';

    public array $headings = [];

    public array $styles = [];

    public array $columnWidths = [];

    public function __construct(
        public Collection $collection
    ) {}

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function setHeadings(array $headings): self
    {
        $this->headings = $headings;

        return $this;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setColumnWidths(array $columnWidths): self
    {
        $this->columnWidths = $columnWidths;

        return $this;
    }

    public function getColumnWidths(): array
    {
        return $this->columnWidths;
    }

    public function setStyles(array $styles): self
    {
        $this->styles = $styles;

        return $this;
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function spreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];

        if (! empty($this->headings)) {
            $rows[] = array_values($this->headings);
        }

        foreach ($this->collection as $row) {
            $rows[] = array_values((array) $row);
        }

        $sheet->fromArray($rows, null, 'A1');

        $this->applyColumnDimensions($sheet);
        $this->applyStyles($sheet);

        return $spreadsheet;
    }

    public function download(): BinaryFileResponse
    {
        $writer = IOFactory::createWriter($this->spreadsheet(), $this->writerType());

        $temporaryPath = tempnam(sys_get_temp_dir(), 'electricgrid_');

        $writer->save($temporaryPath);

        $response = new BinaryFileResponse($temporaryPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->fileName);
        $response->headers->set('Content-Type', $this->contentType());
        $response->deleteFileAfterSend(true);

        return $response;
    }

    protected function applyColumnDimensions(Worksheet $sheet): void
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($index = 1; $index <= $highestColumnIndex; $index++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }

        foreach ($this->columnWidths as $column => $width) {
            $dimension = $sheet->getColumnDimension((string) $column);
            $dimension->setAutoSize(false);
            $dimension->setWidth((float) $width);
        }
    }

    protected function applyStyles(Worksheet $sheet): void
    {
        foreach ($this->styles as $cell => $style) {
            $sheet->getStyle((string) $cell)->applyFromArray($style);
        }
    }

    protected function extension(): string
    {
        return strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
    }

    protected function writerType(): string
    {
        return match ($this->extension()) {
            'csv' => 'Csv',
            'html', 'htm' => 'Html',
            'xls' => 'Xls',
            'ods' => 'Ods',
            'pdf' => 'Mpdf',
            default => 'Xlsx',
        };
    }

    protected function contentType(): string
    {
        return match ($this->extension()) {
            'csv' => 'text/csv',
            'html', 'htm' => 'text/html',
            'xls' => 'application/vnd.ms-excel',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'pdf' => 'application/pdf',
            default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
