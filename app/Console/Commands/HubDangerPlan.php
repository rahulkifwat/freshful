<?php

namespace App\Console\Commands;

use App\Services\HubConsole\HubDangerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * php artisan hub:danger-plan   - builds today's danger-stock transfer plan for every active hub (runs once per hub per day).
 * Optional hPanel cron (every 15 minutes is enough):  php ~/domains/freshful.in/laravelapp/artisan hub:danger-plan
 * Without the cron the plan is still created the first time each hub opens its console after 11:00.
 */
class HubDangerPlan extends Command
{
    protected $signature = 'hub:danger-plan {--force : recalculate today\'s plan even if it already ran}';
    protected $description = 'Plan transfers of danger stock (received yesterday) that will not sell today to nearby hubs that need it';

    public function handle(): int
    {
        $hubs = DB::table('hubs')->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active','1')")->pluck('hub', 'id');
        foreach ($hubs as $id => $name) {
            $r = (new HubDangerService((int) $id))->runDaily((bool) $this->option('force'));
            if (!empty($r['ran'])) $this->line($name . ': ' . ($r['message'] ?? 'done'));
        }
        $this->info('Danger stock plan checked for ' . count($hubs) . ' hub(s).');
        return self::SUCCESS;
    }
}
