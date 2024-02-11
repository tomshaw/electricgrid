<?php

namespace TomShaw\ElectricGrid\Filters;

class FilterTimePicker extends FilterBase
{
    public string $startMin = '00:00';

    public string $startMax = '23:59';

    public int $startStep = 60;

    public string $endMin = '00:00';

    public string $endMax = '23:59';

    public int $endStep = 60;

    public function startAttributes(string $startMin, string $startMax, int $startStep): FilterTimePicker
    {
        $this->startMin = $startMin;
        $this->startMax = $startMax;
        $this->startStep = $startStep;

        return $this;
    }

    public function endAttributes(string $endMin, string $endMax, int $endStep): FilterTimePicker
    {
        $this->endMin = $endMin;
        $this->endMax = $endMax;
        $this->endStep = $endStep;

        return $this;
    }
}
