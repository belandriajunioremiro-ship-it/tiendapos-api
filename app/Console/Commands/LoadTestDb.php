<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class LoadTestDb extends Command
{
    protected $signature = 'db:load-test';
    protected $description = 'Load SQL dump into the current database';
    public function handle()
    {
        $sql = file_get_contents(database_path('TIENDAPOS v2.1.sql'));
        $lines = preg_split('/;\r?\n/', $sql);
        $count = 0; $errors = 0; $skipped = 0;
        foreach ($lines as $i => $stmt) {
            // Strip leading comment lines from multi-line statements
            $stmt = preg_replace('/^\s*--.*$/m', '', $stmt);
            $stmt = trim($stmt);
            if (empty($stmt)) continue;
            if (str_starts_with($stmt, 'CREATE EXTENSION')) { $skipped++; continue; }
            if (str_contains($stmt, '$$')) { $skipped++; continue; }
            try {
                DB::unprepared($stmt);
                $count++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }
        $this->info("OK: $count stmts, $errors errors, $skipped skipped");
        $this->call('migrate', ['--force' => true]);
    }
}
