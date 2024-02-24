<?php

namespace TomShaw\ElectricGrid\Console\Commands;

use Illuminate\Console\Command;
use TomShaw\ElectricGrid\Console\Traits\BuildsAssets;

class InstallCommand extends Command
{
    use BuildsAssets;

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
    protected $description = 'Publishes (config, views, translations) provided by Electric Grid.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Electric Grid Config...');
        $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.config']);

        $this->comment('Publishing Electric Grid Views...');
        $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.views']);

        $this->comment('Publishing Electric Grid Translations...');
        $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.lang']);

        $this->comment('Building Electric Grid Assets...');
        $this->buildAssets();

        $this->info('Electric Grid successfully installed!');
    }
}
