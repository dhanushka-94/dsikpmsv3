<?php

/*
| DSI KPI Monitoring System
| Developed by olexto Digital Solutions - info@olexto.com - https://olexto.com
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeveloperCreditsCommand extends Command
{
    protected $signature = 'developer:credits';

    protected $description = 'Show developer credits for DSI KPI Monitoring System';

    public function handle(): int
    {
        $this->newLine();
        $this->info(config('developer.product'));
        $this->line(str_repeat('─', 52));
        $this->line('Developer : '.config('developer.name'));
        $this->line('Email     : '.config('developer.email'));
        $this->line('Website   : '.config('developer.website'));
        $this->line('Tagline   : '.config('developer.tagline'));
        $this->line(str_repeat('─', 52));
        $this->comment(config('developer.credit_line'));
        $this->newLine();

        return self::SUCCESS;
    }
}
