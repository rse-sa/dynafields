<?php

namespace RSE\DynaFields\Support\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature   = 'dynafields:install';

    protected $description = 'Publish DynaFields config and run its migrations';

    public function handle(): void
    {
        $this->info('Installing DynaFields...');

        $this->call('vendor:publish', [
            '--tag'   => 'dynafields-config',
            '--force' => false,
        ]);

        $this->call('migrate');

        $this->info('DynaFields installed successfully.');
        $this->line('');
        $this->line('Next steps:');
        $this->line('  1. Add <comment>HasCustomFields</comment> trait to models that store field values.');
        $this->line('  2. Override <comment>customFieldOwner()</comment> if fields are grouped by an owner.');
        $this->line('  3. Optionally add <comment>DefinesCustomFields</comment> to owner models.');
        $this->line('  4. In forms: @livewire(\'dynafields::form\', [\'subject\' => $model, \'action\' => \'create\'])');
    }
}
