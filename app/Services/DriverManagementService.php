<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Driver approval and order-to-driver assignment rules, shared by the admin
 * panel (session, /admin/*) and the admin token API (/api/driver/admin/*).
 *
 * Conventions in the existing data:
 * - driver_registration.verification_status: incomplete -> pending_admin_approval -> approved | rejected
 * - driver_registration.status: 'Activate' / 'active' = active, 'Deactivate' = suspended
 * - orders.rider_id holds driver_registration.id (0 = unassigned); one orders row per
 *   product, all rows of an order share order_id and are assigned together.
 */
class DriverManagementService
{
    public const PENDING = 'pending_admin_approval';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    /** Order statuses in which a driver can be assigned or changed (assignment locks once dispatched). */
    public const ASSIGNABLE_ORDER_STATUSES = ['Order Placed', 'Order Processed'];

    private const SUSPENDED_STATUSES = ['deactivate', '0'];

    private const TZ = 'Asia/Kolkata';

    // ---- Drivers -----------------------------------------------------------

    /**
     * Drivers with profile, vehicle and hub, for the list pages.
     * $filter: pending | approved | rejected | suspended | incomplete | null (all)
     */
    public function driversQuery(?string $filter = null, ?string $search = null): Builder
    {
        $query = DB::table('driver_registration as d')
            ->leftJoin('driver_profile as p', 'p.regid', '=', 'd.id')
            ->leftJoin('driver_vehicles as v', 'v.regid', '=', 'd.id')
            ->select(
                'd.id', 'd.email', 'd.phone', 'd.emp_no', 'd.status', 'd.verification_status',
                'd.verified_at', 'd.approved_at', 'd.rejection_reason',
                DB::raw("COALESCE(NULLIF(p.name, ''), NULLIF(d.name, '')) as name"),
                'p.profile_photo', 'p.created_at as profile_created_at',
                'v.vehicle_type', 'v.vehicle_number'
            )
            ->orderByDesc('d.id');

        match ($filter) {
            'pending'    => $query->where('d.verification_status', self::PENDING),
            'approved'   => $query->where('d.verification_status', self::APPROVED),
            'rejected'   => $query->where('d.verification_status', self::REJECTED),
            'incomplete' => $query->where(fn ($q) => $q->whereIn('d.verification_status', ['incomplete', ''])->orWhereNull('d.verification_status')),
            'suspended'  => $this->whereSuspended($query, 'd.status'),
            default      => null,
        };

        if ($search !== null && trim($search) !== '') {
            $like = '%'.trim($search).'%';
            $query->where(fn ($q) => $q->where('d.email', 'like', $like)
                ->orWhere('d.phone', 'like', $like)
                ->orWhere('p.name', 'like', $like)
                ->orWhere('d.name', 'like', $like)
                ->orWhere('v.vehicle_number', 'like', $like));
        }

        return $query;
    }

    /** Everything stored about one driver (password excluded), or null. */
    public function driverDetails(int $regid): ?array
    {
        $driver = DB::table('driver_registration')->where('id', $regid)->first();
        if (! $driver) {
            return null;
        }
        unset($driver->password);

        $first = fn (string $table) => DB::table($table)->where('regid', $regid)->first();

        return [
            'driver'    => $driver,
            'profile'   => $first('driver_profile'),
            'vehicle'   => $first('driver_vehicles'),
            'bank'      => $first('driver_bank_details'),
            'documents' => $first('driver_identity_documents'),
            'wallet'    => $first('driver_wallet'),
            // delivery_boy.regid is a varchar; compare as string.
            'hub'       => DB::table('delivery_boy as b')->leftJoin('hubs as h', 'h.id', '=', 'b.hub_id')
                ->where('b.regid', (string) $regid)->orderByDesc('b.id')->first(['b.hub_id', 'h.hub', 'b.employee_id']),
            'active_orders' => DB::table('orders')->where('rider_id', $regid)
                ->whereIn('order_status', array_merge(self::ASSIGNABLE_ORDER_STATUSES, ['Order Dispatched', 'Order Shipped']))
                ->distinct()->count('order_id'),
            'suspended' => $this->isSuspended($driver->status),
        ];
    }

    public function isSuspended(?string $status): bool
    {
        return in_array(strtolower((string) $status), self::SUSPENDED_STATUSES, true);
    }

    /** Approve a driver awaiting approval; creates their wallet. */
    public function approve(int $regid, int $adminId): void
    {
        DB::transaction(function () use ($regid, $adminId) {
            $now = $this->now();
            $this->transition($regid, self::APPROVED, ['approved_by' => $adminId, 'approved_at' => $now]);

            if (! DB::table('driver_wallet')->where('regid', $regid)->exists()) {
                DB::table('driver_wallet')->insert([
                    'regid' => $regid, 'balance' => 0, 'currency' => 'INR', 'status' => 'active',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });
    }

    /** Reject a driver awaiting approval, with an optional reason. */
    public function reject(int $regid, int $adminId, string $reason = ''): void
    {
        $this->transition($regid, self::REJECTED, [
            'rejection_reason' => mb_substr(trim($reason), 0, 1000),
            'approved_by'      => $adminId,
            'approved_at'      => $this->now(),
        ]);
    }

    /**
     * Only pending_admin_approval -> approved|rejected is allowed. The status is part of
     * the UPDATE's WHERE, so a double click or two admins at once can't apply it twice.
     */
    private function transition(int $regid, string $to, array $extra): void
    {
        $changed = DB::table('driver_registration')
            ->where('id', $regid)
            ->where('verification_status', self::PENDING)
            ->update(['verification_status' => $to] + $extra);

        if ($changed) {
            return;
        }

        $current = DB::table('driver_registration')->where('id', $regid)->value('verification_status');
        if ($current === null && ! DB::table('driver_registration')->where('id', $regid)->exists()) {
            throw new ApiException('Driver not found');
        }

        throw new ApiException(match ($current) {
            self::APPROVED => 'Driver is already approved',
            self::REJECTED => 'Driver is already rejected',
            default        => 'Driver is not pending approval (driver has not submitted verification yet)',
        });
    }

    // ---- Assignment --------------------------------------------------------

    /**
     * Approved, non-suspended drivers that can receive orders, with vehicle, hub and
     * current workload (open assigned orders).
     */
    public function eligibleDriversQuery(?string $search = null): Builder
    {
        $workload = DB::table('orders')
            ->whereIn('order_status', array_merge(self::ASSIGNABLE_ORDER_STATUSES, ['Order Dispatched', 'Order Shipped']))
            ->where('rider_id', '>', 0)
            ->groupBy('rider_id')
            ->select('rider_id', DB::raw('COUNT(DISTINCT order_id) as open_orders'));

        $query = $this->driversQuery('approved', $search)
            ->leftJoinSub($workload, 'w', 'w.rider_id', '=', 'd.id')
            ->addSelect(DB::raw('COALESCE(w.open_orders, 0) as open_orders'));

        return $this->whereNotSuspended($query, 'd.status');
    }

    /**
     * One row per order in an assignable status. $filter: unassigned (default) | assigned | all
     */
    public function assignableOrdersQuery(string $filter = 'unassigned', ?string $search = null): Builder
    {
        // Orders are stored one row per product; summarise per order_id.
        $grouped = DB::table('orders')
            ->whereIn('order_status', self::ASSIGNABLE_ORDER_STATUSES)
            ->groupBy('order_id')
            ->select('order_id', DB::raw('MIN(id) as first_id'), DB::raw('COUNT(*) as items'), DB::raw('MAX(rider_id) as rider_id'));

        $query = DB::table('orders as o')
            ->joinSub($grouped, 'g', 'g.first_id', '=', 'o.id')
            ->leftJoin('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->leftJoin('buyer_address as a', 'a.id', '=', 'o.address_id')
            ->leftJoin('hubs as h', 'h.id', '=', 'o.hub_id')
            ->leftJoin('driver_registration as d', 'd.id', '=', 'g.rider_id')
            ->leftJoin('driver_profile as p', 'p.regid', '=', 'd.id')
            ->select(
                'o.order_id', 'o.order_status', 'o.date_added', 'o.total_amount', 'o.amount_to_collect',
                'o.payment_type', 'o.payment_status', 'o.delivery_type', 'o.schedule_date', 'o.schedule_time',
                'g.items', 'g.rider_id',
                'b.name as buyer_name', 'b.phone as buyer_phone',
                'a.name as address_name', 'a.phone as address_phone', 'a.street_name', 'a.locality',
                'a.landmark', 'a.city', 'h.hub as hub_name',
                DB::raw("COALESCE(NULLIF(p.name, ''), NULLIF(d.name, ''), d.email) as rider_name"), 'd.phone as rider_phone'
            )
            ->orderByDesc('o.id');

        match ($filter) {
            'assigned' => $query->where('g.rider_id', '>', 0),
            'all'      => null,
            default    => $query->where(fn ($q) => $q->whereNull('g.rider_id')->orWhere('g.rider_id', 0)),
        };

        if ($search !== null && trim($search) !== '') {
            $like = '%'.trim($search).'%';
            $query->where(fn ($q) => $q->where('o.order_id', 'like', $like)
                ->orWhere('b.name', 'like', $like)
                ->orWhere('b.phone', 'like', $like)
                ->orWhere('a.phone', 'like', $like));
        }

        return $query;
    }

    /**
     * Assign (or reassign) every row of an order to an eligible driver.
     *
     * $expectedRiderId is the rider the admin saw when opening the dialog (0 = unassigned).
     * The UPDATE only applies if the order still has that rider, so two admins assigning
     * the same order at once can't silently overwrite each other.
     *
     * @return array{order_id: string, rider_id: int, previous_rider_id: int, reassigned: bool}
     */
    public function assign(string $orderId, int $regid, int $expectedRiderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === '' || $regid <= 0) {
            throw new ApiException('order_id and driver are required');
        }

        return DB::transaction(function () use ($orderId, $regid, $expectedRiderId) {
            // Row locks on MySQL; the compare-and-set below also covers engines without them.
            $rows = DB::table('orders')->where('order_id', $orderId)->lockForUpdate()->get(['id', 'order_status', 'rider_id']);
            if ($rows->isEmpty()) {
                throw new ApiException('Order not found');
            }

            $statuses = $rows->pluck('order_status')->unique();
            if ($statuses->diff(self::ASSIGNABLE_ORDER_STATUSES)->isNotEmpty()) {
                throw new ApiException('Order cannot be assigned in status "'.$statuses->implode(', ')
                    .'". Only '.implode(' / ', self::ASSIGNABLE_ORDER_STATUSES).' orders can be assigned.');
            }

            $current = (int) $rows->max('rider_id');
            if ($current === $regid) {
                throw new ApiException('Order is already assigned to this driver');
            }
            if ($current !== $expectedRiderId) {
                throw new ApiException('This order was just assigned to another driver. Refresh the list and try again.');
            }

            $this->assertDriverEligible($regid);

            $updated = DB::table('orders')
                ->where('order_id', $orderId)
                ->whereIn('order_status', self::ASSIGNABLE_ORDER_STATUSES)
                ->where(fn ($q) => $expectedRiderId === 0
                    ? $q->whereNull('rider_id')->orWhere('rider_id', 0)
                    : $q->where('rider_id', $expectedRiderId))
                ->update(['rider_id' => $regid]);

            if ($updated !== $rows->count()) {
                throw new ApiException('This order was just changed by someone else. Refresh the list and try again.');
            }

            return [
                'order_id'          => $orderId,
                'rider_id'          => $regid,
                'previous_rider_id' => $current,
                'reassigned'        => $current > 0,
            ];
        });
    }

    public function assertDriverEligible(int $regid): void
    {
        $driver = DB::table('driver_registration')->where('id', $regid)->lockForUpdate()->first(['verification_status', 'status']);
        if (! $driver) {
            throw new ApiException('Driver not found');
        }
        if ($driver->verification_status !== self::APPROVED) {
            throw new ApiException('Only approved drivers can be assigned orders');
        }
        if ($this->isSuspended($driver->status)) {
            throw new ApiException('This driver is suspended and cannot be assigned orders');
        }
    }

    // ---- helpers -----------------------------------------------------------

    private function whereSuspended(Builder $query, string $column): Builder
    {
        return $query->whereIn(DB::raw("LOWER(COALESCE($column, ''))"), self::SUSPENDED_STATUSES);
    }

    private function whereNotSuspended(Builder $query, string $column): Builder
    {
        return $query->whereNotIn(DB::raw("LOWER(COALESCE($column, ''))"), self::SUSPENDED_STATUSES);
    }

    private function now(): string
    {
        return Carbon::now(self::TZ)->format('Y-m-d H:i:s');
    }
}
