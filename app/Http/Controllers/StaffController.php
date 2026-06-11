<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class StaffController extends Controller
{
    // Each role maps a URL slug onto a backing table, a label, and the URL
    // fragments for the legacy add_*/view_* routes. Adjust if a role gains a
    // unique field (image, hub assignment) — keep the listing/CRUD generic.
    private array $roles = [
        'account_managers' => [
            'table' => 'account_managers', 'label' => 'Account Manager',
            'list_url' => 'account_managers', 'add_url' => 'add_account_manager', 'view_url' => 'view_account_manager',
        ],
        'area_managers' => [
            'table' => 'area_managers', 'label' => 'Area Manager',
            'list_url' => 'area_managers', 'add_url' => 'add_area_manager', 'view_url' => 'view_area_manager',
        ],
        'country_managers' => [
            'table' => 'country_managers', 'label' => 'Country Manager',
            'list_url' => 'country_managers', 'add_url' => 'add_country_manager', 'view_url' => 'view_country_manager',
        ],
        'customer_care_managers' => [
            'table' => 'customer_care_managers', 'label' => 'Customer Care Manager',
            'list_url' => 'customer_care_managers', 'add_url' => 'add_customer_care_manager', 'view_url' => 'view_customer_care_manager',
        ],
        'hr_managers' => [
            'table' => 'hr_managers', 'label' => 'HR Manager',
            'list_url' => 'hr_managers', 'add_url' => 'add_hr_manager', 'view_url' => 'view_hr_manager',
        ],
        'marketing_managers' => [
            'table' => 'marketing_managers', 'label' => 'Marketing Manager',
            'list_url' => 'marketing_managers', 'add_url' => 'add_marketing_manager', 'view_url' => 'view_marketing_manager',
        ],
        'operation_managers' => [
            'table' => 'operation_managers', 'label' => 'Operation Manager',
            'list_url' => 'operation_managers', 'add_url' => 'add_operation_manager', 'view_url' => 'view_operation_manager',
        ],
        'planning_managers' => [
            'table' => 'planning_managers', 'label' => 'Planning Manager',
            'list_url' => 'planning_managers', 'add_url' => 'add_planning_manager', 'view_url' => 'view_planning_manager',
        ],
        'production_user' => [
            'table' => 'production_user', 'label' => 'Production User',
            'list_url' => 'production_user', 'add_url' => 'add_production_user', 'view_url' => 'view_production_user',
        ],
        'pos_users' => [
            'table' => 'pos_users', 'label' => 'POS User',
            'list_url' => 'pos_users', 'add_url' => 'add_pos_user', 'view_url' => 'view_pos_user',
        ],
        'hub_users' => [
            'table' => 'hub_user', 'label' => 'HUB User',
            'list_url' => 'hub_users', 'add_url' => 'add_hub_user', 'view_url' => 'view_hub_user',
        ],
    ];

    private function role(string $key): array
    {
        if (! isset($this->roles[$key])) {
            abort(404, 'Unknown role: '.$key);
        }
        return ['key' => $key] + $this->roles[$key];
    }

    public function index(Request $request, string $role)
    {
        $cfg   = $this->role($role);
        $query = DB::table($cfg['table'].' as t1');

        if ($cfg['table'] === 'hub_user' && Schema::hasTable('hubs')) {
            $query->leftJoin('hubs as t2', 't2.id', '=', 't1.hub_id')
                  ->select('t1.*', 't2.hub as hub_name');
        } else {
            $query->select('t1.*');
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('t1.name', 'like', "%{$s}%")
                  ->orWhere('t1.email', 'like', "%{$s}%")
                  ->orWhere('t1.phone', 'like', "%{$s}%");
            });
        }

        $rows = $query->orderByDesc('t1.id')->paginate(20)->withQueryString();

        return view('admin.staff.list', compact('cfg', 'rows'));
    }

    public function create(string $role)
    {
        $cfg  = $this->role($role);
        $row  = null;
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->orderBy('hub')->get(['id', 'hub']) : collect();
        return view('admin.staff.form', compact('cfg', 'row', 'hubs'));
    }

    public function show(Request $request, string $role)
    {
        $cfg = $this->role($role);
        $id  = (int) $request->query('id');
        $row = $id ? DB::table($cfg['table'])->where('id', $id)->first() : null;

        if (! $row) {
            return redirect()->to('/admin/'.$cfg['list_url'])->with('error', 'Record not found.');
        }

        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->orderBy('hub')->get(['id', 'hub']) : collect();
        return view('admin.staff.form', compact('cfg', 'row', 'hubs'));
    }

    public function store(Request $request, string $role)
    {
        $cfg = $this->role($role);

        $request->validate([
            'name'     => 'required|string|max:191',
            'email'    => 'required|email|max:191',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:4',
            'hub_id'   => 'nullable|integer',
        ]);

        $row = [
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'status'   => 'active',
        ];
        if (Schema::hasColumn($cfg['table'], 'hub_id') && $request->filled('hub_id')) {
            $row['hub_id'] = (int) $request->hub_id;
        }
        if (Schema::hasColumn($cfg['table'], 'created_at')) {
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table($cfg['table'])->insert($row);

        return redirect()->to('/admin/'.$cfg['list_url'])->with('success', $cfg['label'].' added.');
    }

    public function update(Request $request, string $role)
    {
        $cfg = $this->role($role);
        $id  = (int) $request->input('id');
        if (! $id || ! DB::table($cfg['table'])->where('id', $id)->exists()) {
            return redirect()->to('/admin/'.$cfg['list_url'])->with('error', 'Record not found.');
        }

        $request->validate([
            'name'     => 'required|string|max:191',
            'email'    => 'required|email|max:191',
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|string|min:4',
            'hub_id'   => 'nullable|integer',
        ]);

        $row = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];
        if ($request->filled('password')) {
            $row['password'] = Hash::make($request->password);
        }
        if (Schema::hasColumn($cfg['table'], 'hub_id') && $request->filled('hub_id')) {
            $row['hub_id'] = (int) $request->hub_id;
        }
        if (Schema::hasColumn($cfg['table'], 'updated_at')) {
            $row['updated_at'] = now();
        }

        DB::table($cfg['table'])->where('id', $id)->update($row);

        return redirect()->to('/admin/'.$cfg['list_url'])->with('success', $cfg['label'].' updated.');
    }
}