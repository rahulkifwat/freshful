<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): list / add / edit / switch on-off for the master screens
 * described in AdminResources. Safety rules (the old admin had none of these):
 *   - only the tables and columns described there can be written (the old pages let the browser name any table);
 *   - required fields, lengths, numbers and dates are checked on the server;
 *   - business records are switched off, never deleted; lookup lists can be deleted (with a check that nothing uses them);
 *   - every change goes to the change log with the old and new values.
 */
class AdminRecords
{
    private static array $statusVals = [];

    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    /** [on, off] values of a table's status column, matching what the table already uses (active / disabled, active / inactive, 1 / 0). */
    public static function statusValues(string $table): array
    {
        if (isset(self::$statusVals[$table])) return self::$statusVals[$table];
        $on = 'active'; $off = 'disabled';
        try {
            $col = DB::select('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` WHERE Field = ?', ['status']);
            $type = strtolower((string) ($col[0]->Type ?? ''));
            if (preg_match('/^enum\((.*)\)$/', $type, $m)) {
                $vals = array_map(fn ($v) => trim($v, "'"), str_getcsv($m[1], ',', "'"));
                $on = in_array('active', $vals, true) ? 'active' : ($vals[0] ?? 'active');
                $off = collect(['disabled', 'inactive', 'deactive', 'block', 'blocked'])->first(fn ($v) => in_array($v, $vals, true)) ?? ($vals[1] ?? $vals[0] ?? 'inactive');
            } elseif (preg_match('/int|bit|bool/', $type)) {
                $on = '1'; $off = '0';
            } else {
                $used = DB::table($table)->select('status')->distinct()->limit(20)->pluck('status')->map(fn ($v) => strtolower(trim((string) $v)))->all();
                if (in_array('1', $used, true) || in_array('0', $used, true)) { $on = '1'; $off = '0'; }
                elseif (in_array('inactive', $used, true) && !in_array('disabled', $used, true)) $off = 'inactive';
            }
        } catch (\Throwable $e) {}
        return self::$statusVals[$table] = [$on, $off];
    }

    public static function isOn($v): bool { return in_array(strtolower(trim((string) $v)), ['active', '1', 'yes', 'on', 'enable', 'enabled'], true); }

    /** Options for select fields. */
    public static function options(string $src): array
    {
        try {
            switch ($src) {
                case 'maincat_names': return DB::table('categories')->orderBy('name')->pluck('name', 'name')->map(fn ($v) => (string) $v)->all();
                case 'maincat_ids': return DB::table('categories')->orderBy('name')->pluck('name', 'id')->map(fn ($v) => (string) $v)->all();
                case 'categories':
                    $o = [];
                    foreach (DB::table('category')->orderBy('group_type')->orderBy('ranking')->orderBy('name')->get(['id', 'name', 'group_type']) as $c) $o[(string) $c->id] = trim($c->name . ($c->group_type ? ' (' . $c->group_type . ')' : ''));
                    return $o;
                case 'states_in':
                    $b = DB::table('states')->orderBy('name');
                    if (self::h('states', 'country_id')) $b->where('country_id', (int) config('admin_console.country_id', 101));
                    return $b->pluck('name', 'id')->map(fn ($v) => (string) $v)->all();
                case 'cities': return DB::table('cities')->orderBy('city')->pluck('city', 'id')->map(fn ($v) => (string) $v)->all();
                case 'tags': return DB::table('product_tags')->orderBy('tag')->pluck('tag', 'tag')->map(fn ($v) => (string) $v)->all();
                case 'units': return DB::table('product_units')->orderBy('unit')->pluck('unit', 'unit')->map(fn ($v) => (string) $v)->all();
            }
        } catch (\Throwable $e) {}
        return [];
    }

    /** Field list for the screen (columns missing in this database are left out). */
    public static function def(string $key): ?array
    {
        $d = AdminResources::get($key);
        if (!$d || !self::h($d['table'])) return null;
        $fields = [];
        foreach ($d['fields'] as $name => $f) {
            if (!self::h($d['table'], $name)) continue;
            $f['name'] = $name;
            if (!empty($f['src'])) $f['opts'] = self::options($f['src']);
            $fields[$name] = $f;
        }
        $d['fields'] = $fields;
        $d['list'] = array_values(array_filter($d['list'], fn ($c) => isset($fields[$c]) || $c === 'used' || ($c === 'created_at' && self::h($d['table'], 'created_at'))));
        $d['key'] = $key;
        $d['has_status'] = isset($fields['status']);
        $d['add'] = !($d['readonly'] ?? false) && ($d['add'] ?? true);
        $d['edit'] = !($d['readonly'] ?? false);
        return $d;
    }

    public static function meta(): array
    {
        $o = [];
        foreach (array_keys(AdminResources::all()) as $k) {
            $d = self::def($k);
            if (!$d) { $o[$k] = ['missing' => true, 'table' => AdminResources::get($k)['table']]; continue; }
            $o[$k] = ['title' => $d['title'], 'plural' => $d['plural'], 'module' => $d['module'], 'help' => $d['help'] ?? '', 'fields' => array_values($d['fields']), 'list' => $d['list'],
                'add' => $d['add'], 'edit' => $d['edit'], 'delete' => !empty($d['delete']), 'has_status' => $d['has_status']];
        }
        return $o;
    }

    public static function list(string $key, string $q = '', string $status = ''): array
    {
        $d = self::def($key);
        if (!$d) return ['ok' => false, 'message' => 'This screen is not available: its table is missing.'];
        $b = DB::table($d['table']);
        $q = trim($q);
        if ($q !== '' && $d['search']) {
            $b->where(function ($w) use ($d, $q) { foreach ($d['search'] as $c) if (self::h($d['table'], $c)) $w->orWhere($c, 'like', '%' . $q . '%'); if (ctype_digit($q)) $w->orWhere('id', (int) $q); });
        }
        if ($status !== '' && $d['has_status']) {
            [$on, $off] = self::statusValues($d['table']);
            $status === 'on' ? $b->whereRaw('CAST(status AS CHAR) IN (?, ?)', [$on, 'active']) : $b->whereRaw('CAST(status AS CHAR) NOT IN (?, ?)', [$on, 'active']);
        }
        [$oc, $od] = $d['order'];
        if (self::h($d['table'], $oc)) $b->orderBy($oc, $od);
        $b->orderBy('id', 'desc');
        $rows = [];
        $limit = (int) config('admin_console.list_limit', 5000);
        foreach ($b->limit($limit + 1)->get() as $r) $rows[] = self::present($d, $r);
        $more = count($rows) > $limit; if ($more) array_pop($rows);
        $rows = self::hookList($key, $rows);
        $total = DB::table($d['table'])->count();
        return ['ok' => true, 'rows' => $rows, 'total' => $total, 'more' => $more];
    }

    private static function present(array $d, $r): array
    {
        $a = (array) $r; $o = ['id' => (int) ($a['id'] ?? 0)];
        foreach ($d['fields'] as $name => $f) {
            $v = $a[$name] ?? null;
            if ($f['t'] === 'image') { $o[$name] = (string) $v === 'false' ? '' : (string) $v; $o[$name . '_url'] = AdminUploads::url($f['folder'] ?? '', $v); continue; }
            if ($f['t'] === 'status') { $o[$name] = self::isOn($v); $o['status_raw'] = (string) $v; continue; }
            if ($f['t'] === 'date') { $o[$name] = (string) $v === '0' || (string) $v === '0000-00-00' ? '' : (string) $v; continue; }
            $o[$name] = $v === null ? '' : (string) $v;
            if (!empty($f['opts']) && $v !== null && $v !== '') $o[$name . '_label'] = $f['opts'][(string) $v] ?? null;
        }
        if (isset($a['created_at']) && !isset($o['created_at'])) $o['created_at'] = (string) $a['created_at'];
        return $o;
    }

    private static function hookList(string $key, array $rows): array
    {
        try {
            if ($key === 'promos' && self::h('buyer_coupon_relation')) {
                $used = DB::table('buyer_coupon_relation')->select('promo_id', DB::raw('COUNT(*) AS n'), DB::raw('SUM(discount) AS d'))->groupBy('promo_id')->get()->keyBy('promo_id');
                foreach ($rows as &$r) { $u = $used[$r['id']] ?? null; $r['used'] = $u ? (int) $u->n : 0; $r['used_value'] = $u ? round((float) $u->d, 2) : 0; $r['expired'] = $r['expiry'] !== '' && $r['expiry'] < date('Y-m-d'); }
                unset($r);
            }
            if ($key === 'items' && self::h('products', 'item_id')) {
                $n = DB::table('products')->select('item_id', DB::raw('COUNT(*) AS n'))->groupBy('item_id')->pluck('n', 'item_id');
                foreach ($rows as &$r) $r['products'] = (int) ($n[$r['id']] ?? 0);
                unset($r);
            }
            if ($key === 'maincat' && self::h('category', 'group_type')) {
                $n = DB::table('category')->select('group_type', DB::raw('COUNT(*) AS n'))->groupBy('group_type')->pluck('n', 'group_type');
                foreach ($rows as &$r) $r['categories'] = (int) ($n[$r['name']] ?? 0);
                unset($r);
            }
        } catch (\Throwable $e) {}
        return $rows;
    }

    /** Check and clean the typed values. Returns [values, error]. */
    private static function clean(array $d, array $in, ?object $old): array
    {
        $out = [];
        foreach ($d['fields'] as $name => $f) {
            if (!empty($f['ro'])) continue;
            if ($f['t'] === 'status') { if (array_key_exists($name, $in)) { [$on, $off] = self::statusValues($d['table']); $out[$name] = filter_var($in[$name], FILTER_VALIDATE_BOOLEAN) ? $on : $off; } elseif (!$old) { $out[$name] = self::statusValues($d['table'])[0]; } continue; }
            if (!array_key_exists($name, $in)) { if (!$old && !empty($f['req']) && $f['t'] !== 'image') return [null, $f['label'] . ' is required.']; continue; }
            $v = $in[$name];
            if (is_array($v)) $v = implode(',', array_map(fn ($x) => trim((string) $x), $v));
            $v = trim((string) $v);
            switch ($f['t']) {
                case 'number': case 'money':
                    if ($v === '') { if (!empty($f['req'])) return [null, $f['label'] . ' is required.']; $v = '0'; break; }
                    if (!is_numeric($v)) return [null, $f['label'] . ' must be a number.'];
                    if (isset($f['min']) && (float) $v < $f['min']) return [null, $f['label'] . ' cannot be less than ' . $f['min'] . '.'];
                    if (isset($f['max']) && (float) $v > $f['max']) return [null, $f['label'] . ' cannot be more than ' . $f['max'] . '.'];
                    $v = $f['t'] === 'money' ? (string) round((float) $v, 2) : CountryScope::q($v);
                    break;
                case 'date':
                    if ($v === '') { $v = '0'; break; }
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) || !strtotime($v)) return [null, $f['label'] . ' is not a valid date.'];
                    break;
                case 'select':
                    if ($v === '') { if (!empty($f['req'])) return [null, 'Choose ' . strtolower($f['label']) . '.']; break; }
                    if (!array_key_exists($v, $f['opts'] ?? [])) return [null, 'Choose ' . strtolower($f['label']) . ' from the list.'];
                    break;
                case 'email':
                    if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) return [null, 'Enter a valid email address.'];
                    break;
                case 'code':
                    $v = strtoupper(preg_replace('/\s+/', '', $v));
                    if ($v !== '' && !preg_match('/^[A-Z0-9_\-]{2,40}$/', $v)) return [null, $f['label'] . ': use letters, numbers, - or _ only (no spaces).'];
                    break;
                case 'csv':
                    $parts = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $v)), fn ($x) => $x !== '')));
                    $v = implode(',', $parts);
                    break;
                case 'image':
                    if ($v !== '' && !preg_match('/^[A-Za-z0-9_\-\.]{1,120}$/', $v)) return [null, 'Upload the image again.'];
                    break;
                case 'html': break;
                case 'citypick': $v = trim(preg_replace('/\s+/', ' ', $v)); break;
                default: $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v);
            }
            if (!empty($f['req']) && ($v === '' || ($f['t'] === 'image' && $v === '' && !$old))) return [null, $f['label'] . ' is required.'];
            if (isset($f['max']) && in_array($f['t'], ['text', 'textarea', 'html', 'csv', 'code', 'email', 'citypick'], true) && mb_strlen($v) > $f['max']) return [null, $f['label'] . ' is too long (at most ' . $f['max'] . ' characters).'];
            if (!empty($f['unique']) && $v !== '') {
                $dup = DB::table($d['table'])->where($name, $v); if ($old) $dup->where('id', '!=', $old->id);
                if ($dup->exists()) return [null, $f['label'] . ' "' . $v . '" already exists.'];
            }
            $out[$name] = $v;
        }
        foreach ($d['fields'] as $name => $f) if ($f['t'] === 'image' && !empty($f['req']) && !$old && empty($out[$name])) return [null, $f['label'] . ' is required.'];
        return [$out, null];
    }

    public static function save(string $key, ?int $id, array $in, array $actor): array
    {
        $d = self::def($key);
        if (!$d || !$d['edit']) return ['ok' => false, 'message' => 'This list cannot be changed here.'];
        $old = $id ? DB::table($d['table'])->where('id', $id)->first() : null;
        if ($id && !$old) return ['ok' => false, 'message' => 'That record no longer exists.'];
        if (!$id && !$d['add']) return ['ok' => false, 'message' => 'New records cannot be added here.'];
        [$vals, $err] = self::clean($d, $in, $old);
        if ($err) return ['ok' => false, 'message' => $err];
        $now = date('Y-m-d H:i:s');
        if (!$old) {
            foreach ($d['fields'] as $name => $f) if (!empty($f['auto_next']) && (!isset($vals[$name]) || $vals[$name] === '' || $vals[$name] === '0')) $vals[$name] = (string) ((int) DB::table($d['table'])->max($name) + 1);
            if (!empty($d['stamp']) && self::h($d['table'], $d['stamp'])) $vals[$d['stamp']] = $now;
            if (!empty($d['stamps'])) { if (self::h($d['table'], 'created_at')) $vals['created_at'] = $now; if (self::h($d['table'], 'updated_at')) $vals['updated_at'] = $now; }
        } elseif (!empty($d['stamps']) && self::h($d['table'], 'updated_at')) $vals['updated_at'] = $now;
        if (!$vals) return ['ok' => true, 'message' => 'Nothing changed.', 'id' => $id];
        $label = self::label($d, $vals + (array) $old);
        DB::beginTransaction();
        try {
            if ($old) {
                DB::table($d['table'])->where('id', $id)->update($vals);
                if (($d['hook'] ?? '') === 'maincat' && isset($vals['name']) && $vals['name'] !== (string) $old->name && self::h('category', 'group_type')) {
                    DB::table('category')->where('group_type', (string) $old->name)->update(['group_type' => $vals['name']]);
                }
            } else {
                $id = (int) DB::table($d['table'])->insertGetId($vals);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['ok' => false, 'message' => 'Could not save: ' . self::dbError($e)];
        }
        $new = DB::table($d['table'])->where('id', $id)->first();
        AdminAudit::log($actor, $old ? 'edit' : 'add', $d['module'], $d['table'], $id, ($old ? 'Edited ' : 'Added ') . strtolower($d['title']) . ' "' . $label . '"', $old, $new);
        return ['ok' => true, 'message' => $d['title'] . ($old ? ' saved.' : ' added.'), 'id' => $id, 'row' => $new ? self::present($d, $new) : null];
    }

    public static function toggle(string $key, int $id, array $actor): array
    {
        $d = self::def($key);
        if (!$d || !$d['edit'] || !$d['has_status']) return ['ok' => false, 'message' => 'This record has no on / off switch.'];
        $old = DB::table($d['table'])->where('id', $id)->first();
        if (!$old) return ['ok' => false, 'message' => 'That record no longer exists.'];
        [$on, $off] = self::statusValues($d['table']);
        $to = self::isOn($old->status) ? $off : $on;
        DB::table($d['table'])->where('id', $id)->update(['status' => $to]);
        $label = self::label($d, (array) $old);
        AdminAudit::log($actor, self::isOn($to) ? 'switch on' : 'switch off', $d['module'], $d['table'], $id, (self::isOn($to) ? 'Switched on ' : 'Switched off ') . strtolower($d['title']) . ' "' . $label . '"', $old, DB::table($d['table'])->where('id', $id)->first());
        return ['ok' => true, 'on' => self::isOn($to), 'message' => '"' . $label . '" is now ' . (self::isOn($to) ? 'on.' : 'off.')];
    }

    public static function delete(string $key, int $id, array $actor): array
    {
        $d = self::def($key);
        if (!$d || empty($d['delete'])) return ['ok' => false, 'message' => 'Records here are switched off, not deleted.'];
        $old = DB::table($d['table'])->where('id', $id)->first();
        if (!$old) return ['ok' => false, 'message' => 'That record no longer exists.'];
        if ($d['delete'] === 'unit_unused' && self::h('products', 'product_unit') && DB::table('products')->where('product_unit', (string) $old->unit)->exists()) {
            return ['ok' => false, 'message' => 'Products use the unit "' . $old->unit . '". Change those products first.'];
        }
        if ($d['delete'] === 'promo_unused' && self::h('buyer_coupon_relation') && DB::table('buyer_coupon_relation')->where('promo_id', $id)->exists()) {
            return ['ok' => false, 'message' => 'Customers have already used this code, so it is kept for the order history. Set an expiry date in the past to stop it.'];
        }
        $block = self::usedBy($d['delete'], $old);
        if ($block) return ['ok' => false, 'used' => true, 'message' => $block];
        DB::table($d['table'])->where('id', $id)->delete();
        AdminAudit::log($actor, 'delete', $d['module'], $d['table'], $id, 'Deleted ' . strtolower($d['title']) . ' "' . self::label($d, (array) $old) . '"', $old, null);
        return ['ok' => true, 'message' => 'Deleted. (A copy is kept in the change log.)'];
    }

    /** Why a record cannot be deleted (it is still in use), or null. */
    public static function usedBy($rule, object $old): ?string
    {
        if (!is_string($rule)) return null;   // switch() compares loosely: true would match the first case
        try {
            switch ($rule) {
                case 'maincat_unused':
                    $n = self::h('category', 'group_type') ? DB::table('category')->where('group_type', (string) $old->name)->count() : 0;
                    return $n ? $n . ' sub categor' . ($n === 1 ? 'y uses' : 'ies use') . ' "' . $old->name . '". Move or delete them first, or switch this category off.' : null;
                case 'category_unused':
                    $p = self::h('products', 'category_id') ? DB::table('products')->where('category_id', $old->id)->count() : 0;
                    $i = self::h('items', 'cat_id') ? DB::table('items')->where('cat_id', $old->id)->count() : 0;
                    return $p || $i ? $p . ' product(s) are in "' . $old->name . '". Move them to another sub category first, or switch it off.' : null;
                case 'tag_unused':
                    $n = self::h('products', 'tag') ? DB::table('products')->whereRaw('FIND_IN_SET(?, REPLACE(tag, ", ", ","))', [(string) $old->tag])->count() : 0;
                    return $n ? $n . ' product(s) carry the tag "' . $old->tag . '". Remove it from them first, or switch the tag off.' : null;
                case 'city_unused':
                    $h = DB::table('hubs')->where('city_id', $old->id)->count();
                    return $h ? $h . ' hub(s) are in ' . $old->city . '. Move or close them first, or switch the city off.' : null;
                case 'grievcat_unused':
                    foreach (['grievance', 'grievances'] as $t) if (self::h($t, 'category_id') && DB::table($t)->where('category_id', $old->id)->exists()) return 'Complaints are filed under "' . $old->name . '", so it is kept for their history.';
                    return null;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    private static function label(array $d, array $v): string
    {
        foreach (['name', 'title', 'item', 'tag', 'unit', 'city', 'property_name', 'type', 'email'] as $c) if (!empty($v[$c])) return mb_substr((string) $v[$c], 0, 60);
        return '#' . ($v['id'] ?? '');
    }

    public static function dbError(\Throwable $e): string
    {
        $m = $e->getMessage();
        if (stripos($m, 'Duplicate') !== false) return 'that value already exists.';
        if (stripos($m, 'Data too long') !== false) return 'a value is too long for the database.';
        if (stripos($m, 'Incorrect') !== false || stripos($m, 'truncated') !== false) return 'a value has the wrong format for the database.';
        return 'database error.';
    }
}
