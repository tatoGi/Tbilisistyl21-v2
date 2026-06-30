<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only reconnaissance of the old Payload CMS (Neon) database. Lists every
 * table with its row count so we can design the ETL mapping into the Laravel
 * models. Performs SELECT queries only -- it never writes to Neon.
 */
class LegacyInspect extends Command
{
    protected $signature = 'legacy:inspect {--table= : Show column layout + a sample row for one table}';

    protected $description = 'Inspect the legacy Payload/Neon database (read-only)';

    public function handle(): int
    {
        try {
            DB::connection('legacy')->getPdo();
            // Belt-and-suspenders: forbid any write on this session, even if the
            // connection were ever pointed straight at production Neon.
            DB::connection('legacy')->statement('SET SESSION default_transaction_read_only = on');
        } catch (Throwable $e) {
            $this->error('Could not connect to the legacy DB. Run `php artisan legacy:snapshot` first, or set LEGACY_DATABASE_URL.');
            $this->line($e->getMessage());
            return self::FAILURE;
        }

        if ($table = $this->option('table')) {
            return $this->inspectTable($table);
        }

        $tables = DB::connection('legacy')
            ->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name");

        $rows = [];
        $total = 0;
        foreach ($tables as $t) {
            $name = $t->table_name;
            try {
                $count = (int) DB::connection('legacy')->table($name)->count();
            } catch (Throwable $e) {
                $count = -1;
            }
            $total += max($count, 0);
            $rows[] = [$name, $count < 0 ? 'error' : number_format($count)];
        }

        $this->info('Legacy tables (' . count($rows) . '), total rows: ' . number_format($total));
        $this->table(['table', 'rows'], $rows);
        $this->newLine();
        $this->line('Tip: php artisan legacy:inspect --table=pages   to see columns + a sample.');

        return self::SUCCESS;
    }

    private function inspectTable(string $table): int
    {
        $columns = DB::connection('legacy')->select(
            "SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? ORDER BY ordinal_position",
            [$table]
        );

        if (empty($columns)) {
            $this->error("Table '{$table}' not found in the legacy DB.");
            return self::FAILURE;
        }

        $this->info("Columns of '{$table}':");
        $this->table(
            ['column', 'type'],
            array_map(fn ($c) => [$c->column_name, $c->data_type], $columns)
        );

        $sample = DB::connection('legacy')->table($table)->limit(1)->first();
        if ($sample) {
            $this->newLine();
            $this->info('Sample row:');
            $this->line(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
