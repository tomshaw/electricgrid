<?php

namespace TomShaw\ElectricGrid\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'electricgrid:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs and publishes (config, views) provided by ElectricGrid.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing ElectricGrid Config...');
        $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.config']);

        $this->comment('Publishing ElectricGrid Views...');
        $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.views']);

        $this->comment('Building ElectricGrid Assets...');
        $this->buildAssets();

        $this->info('ElectricGrid installed successfully!');
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
