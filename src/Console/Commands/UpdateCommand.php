<?php

namespace TomShaw\ElectricGrid\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Process\Process;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'electricgrid:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates and publishes (config, views) provided by ElectricGrid.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('This will overwrite ElectricGrid (config, views).');

        if ($this->confirm('Do you wish to continue?', true)) {
            $this->comment('Updating ElectricGrid Config...');
            $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.config']);

            $this->comment('Updating ElectricGrid Assets...');
            $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.views']);

            $this->comment('Building ElectricGrid Assets...');
            $this->buildAssets();
        }

        $this->info('ElectricGrid updated successfully!');
    }

    private function buildAssets()
    {
        $process = new Process(['npm', 'run', 'build']);

        $process->setWorkingDirectory(base_path())
            ->setTimeout(null)
            ->run(function ($type, $buffer) {
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
