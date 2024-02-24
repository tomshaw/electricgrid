<?php

namespace TomShaw\ElectricGrid\Console\Commands;

use Illuminate\Console\Command;
use TomShaw\ElectricGrid\Console\Traits\BuildsAssets;

class UpdateCommand extends Command
{
    use BuildsAssets;

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
    protected $description = 'Publishes (config, views, translations) provided by Electric Grid.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('This will overwrite Electric Grid (config, views, translations).');

        if ($this->confirm('Do you wish to continue?', true)) {
            $this->comment('Updating Electric Grid Config...');
            $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.config', '--force' => true]);

            $this->comment('Updating Electric Grid Views...');
            $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.views', '--force' => true]);

            $this->comment('Updating Electric Grid Translations...');
            $this->callSilent('vendor:publish', ['--tag' => 'electricgrid.lang', '--force' => true]);

            $this->comment('Building Electric Grid Assets...');
            $this->buildAssets();
        }

        $this->info('Electric Grid successfully updated!');
    }
}
