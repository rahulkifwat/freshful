<?php

namespace App\Services\ProductionConsole;

use Illuminate\Support\Facades\Log;

/** Freshful production console (production update 1, 28 Oct 2026). Change log: the admin change log (admin_audit_log, when installed) plus a "[freshful-audit]" line. */
class ProductionLog
{
    public static function write(array $actor, string $action, string $table, $id, string $summary, $before = null, $after = null): void
    {
        try { Log::info('[freshful-audit] production-' . $action, ['by' => $actor['id'] ?? null, 'name' => $actor['name'] ?? null, 'record' => $id, 'what' => $summary]); } catch (\Throwable $e) {}
        if (class_exists(\App\Services\AdminConsole\AdminAudit::class)) {
            try { \App\Services\AdminConsole\AdminAudit::log($actor, $action, 'Production', $table, $id, $summary, $before, $after); } catch (\Throwable $e) {}
        }
    }
}
