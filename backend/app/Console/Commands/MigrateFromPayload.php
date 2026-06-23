<?php

namespace App\Console\Commands;

use App\Actions\MigratePayloadDataAction;
use Illuminate\Console\Command;

class MigrateFromPayload extends Command
{
    protected $signature = 'migrate:from-payload {--source-dsn= : Source PostgreSQL DSN or connection name}';
    protected $description = 'One-time migration from Payload CMS PostgreSQL to Laravel schema';

    public function handle(MigratePayloadDataAction $action): int
    {
        $dsn = $this->option('source-dsn');
        if (!$dsn) {
            $this->error('--source-dsn is required');
            return 1;
        }

        $this->info('Starting Payload → Laravel data migration...');
        $action->execute($dsn, $this->output);
        $this->info('Migration complete.');

        return 0;
    }
}
