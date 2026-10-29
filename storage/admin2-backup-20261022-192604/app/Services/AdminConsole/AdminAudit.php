<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): change log. Every save, switch on/off, password reset and
 * import made in the new admin is written to admin_audit_log with the values before and after. Passwords are never logged.
 */
class AdminAudit
{
    private const HIDE = ['password', 'otp', 'f_otp', 'auth_id', 'regId', 'fcm_id', 'remember_token'];

    public static function on(): bool { return CountryScope::has('admin_audit_log'); }

    private static function clean($row): ?array
    {
        if ($row === null) return null;
        $a = (array) $row;
        foreach (self::HIDE as $k) if (array_key_exists($k, $a)) $a[$k] = '(hidden)';
        return $a;
    }

    public static function log(array $actor, string $action, string $module, string $table, $recordId, string $summary, $before = null, $after = null): void
    {
        if (!self::on()) return;
        try {
            $b = self::clean($before); $a = self::clean($after);
            if ($b !== null && $a !== null) { // keep only what changed
                $cb = []; $ca = [];
                foreach (array_unique(array_merge(array_keys($b), array_keys($a))) as $k) {
                    if ((string) ($b[$k] ?? '') !== (string) ($a[$k] ?? '')) { $cb[$k] = $b[$k] ?? null; $ca[$k] = $a[$k] ?? null; }
                }
                $b = $cb; $a = $ca;
            }
            DB::table('admin_audit_log')->insert([
                'actor' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'actor_role' => mb_substr((string) ($actor['role'] ?? ''), 0, 30), 'actor_id' => (int) ($actor['id'] ?? 0),
                'action' => mb_substr($action, 0, 40), 'module' => mb_substr($module, 0, 60), 'table_name' => mb_substr($table, 0, 60), 'record_id' => mb_substr((string) $recordId, 0, 60),
                'summary' => mb_substr($summary, 0, 255), 'before_json' => $b === null ? null : json_encode($b, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'after_json' => $a === null ? null : json_encode($a, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), 'ip' => mb_substr((string) (request()->ip() ?? ''), 0, 45),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // The change itself has already been saved; a log failure must not undo it.
        }
    }

    public static function list(string $from, string $to, string $q, string $module, int $limit = 1000): array
    {
        if (!self::on()) return ['ok' => true, 'rows' => [], 'modules' => [], 'off' => true];
        $b = DB::table('admin_audit_log')->where('created_at', '>=', $from . ' 00:00:00')->where('created_at', '<=', $to . ' 23:59:59');
        if ($module !== '') $b->where('module', $module);
        $q = trim($q);
        if ($q !== '') $b->where(function ($w) use ($q) { $w->where('summary', 'like', '%' . $q . '%')->orWhere('actor', 'like', '%' . $q . '%')->orWhere('record_id', $q); });
        $rows = [];
        foreach ($b->orderByDesc('id')->limit($limit)->get() as $r) {
            $rows[] = ['id' => (int) $r->id, 'at' => CountryScope::ts($r->created_at), 'actor' => (string) $r->actor, 'role' => (string) $r->actor_role, 'action' => (string) $r->action,
                'module' => (string) $r->module, 'table' => (string) $r->table_name, 'record' => (string) $r->record_id, 'summary' => (string) $r->summary,
                'before' => $r->before_json ? json_decode($r->before_json, true) : null, 'after' => $r->after_json ? json_decode($r->after_json, true) : null, 'ip' => (string) $r->ip];
        }
        $mods = DB::table('admin_audit_log')->select('module')->distinct()->orderBy('module')->pluck('module')->all();
        return ['ok' => true, 'rows' => $rows, 'modules' => $mods];
    }
}
