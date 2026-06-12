<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Console\Traits;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

trait BuildsAssets
{
    private function buildAssets(): void
    {
        $process = new Process(['npm', 'run', 'build']);

        $process->setWorkingDirectory(base_path())
            ->setTimeout(null)
            ->run(function (string $type, string $buffer) {
                if ($type === Process::ERR) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

        if (! $process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }
    }
}
