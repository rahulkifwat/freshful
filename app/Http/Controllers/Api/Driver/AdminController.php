<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\ApiException;
use App\Services\DriverManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Driver onboarding actions for admins — was api/driver/admin/*.php (which had no auth).
 * All routes need an admin token (admin.api middleware); the acting admin comes from
 * the token, so a request can't claim to be another admin via admin_id.
 */
class AdminController extends DriverController
{
    /** /api/driver/admin/pending-drivers */
    public function pending(): JsonResponse
    {
        $drivers = $this->withoutPasswords(DB::table('driver_registration')
            ->where('verification_status', 'pending_admin_approval')
            ->orderBy('verified_at')
            ->get());

        return $this->respond(['result' => true, 'message' => 'success', 'drivers' => $drivers, 'count' => count($drivers)]);
    }

    /** /api/driver/users — all drivers (admin only; legacy allowed any driver token) */
    public function users(): JsonResponse
    {
        $drivers = $this->withoutPasswords(DB::table('driver_registration')->get());

        return $this->respond(['result' => true, 'message' => 'success', 'users' => $drivers, 'count' => count($drivers)]);
    }

    /** POST /api/driver/admin/driver-verify — regid; checks onboarding is complete */
    public function verify(Request $request): JsonResponse
    {
        $regid = $this->requireFilled($request, 'regid');
        $adminId = $request->user()->id;

        $status = $this->verificationStatus($regid);
        if ($status === 'approved') {
            throw new ApiException('Driver already approved');
        }
        if ($status === 'pending_admin_approval') {
            throw new ApiException('Driver already verified and pending admin approval');
        }

        $vehicle = $this->firstRow('driver_vehicles', $regid);
        if (! $vehicle) {
            throw new ApiException('Vehicle details are required for verification');
        }
        if (empty($vehicle['vehicle_type']) || empty($vehicle['vehicle_number'])) {
            throw new ApiException('Vehicle type and number are required');
        }

        $bank = $this->firstRow('driver_bank_details', $regid);
        if (! $bank) {
            throw new ApiException('Bank details are required for verification');
        }
        foreach (['account_holder_name', 'account_number', 'ifsc_code', 'bank_name', 'branch_name'] as $field) {
            if (empty($bank[$field])) {
                throw new ApiException('Complete bank details are required for verification');
            }
        }

        $docs = $this->firstRow('driver_identity_documents', $regid);
        if (! $docs) {
            throw new ApiException('Identity documents are required for verification');
        }
        foreach (['aadhaar_card_front', 'aadhaar_card_back', 'driving_license_front', 'driving_license_back', 'pan_card_front', 'pan_card_back'] as $field) {
            if (empty($docs[$field])) {
                throw new ApiException('All identity document sides (Aadhaar, DL, PAN) are required for verification');
            }
        }

        DB::table('driver_registration')->where('id', $regid)->update([
            'verification_status' => 'pending_admin_approval',
            'verified_at'         => $this->now(),
            'approved_by'         => $adminId,
        ]);

        return $this->respond(['result' => true, 'message' => 'Driver verified successfully and moved to pending admin approval']);
    }

    /** POST /api/driver/admin/driver-approval — regid, approve (true/false), rejection_reason(o) */
    public function approval(Request $request, DriverManagementService $drivers): JsonResponse
    {
        $regid = (int) $this->requireFilled($request, 'regid');
        $adminId = (int) $request->user()->id;
        if (! $request->exists('approve')) {
            throw new ApiException('approve (true/false) is required');
        }

        // Same rules as the admin panel (only pending -> approved/rejected, no double approval).
        if (! filter_var($request->input('approve'), FILTER_VALIDATE_BOOLEAN)) {
            $drivers->reject($regid, $adminId, (string) $request->input('rejection_reason', ''));

            return $this->respond(['result' => true, 'message' => 'Driver rejected']);
        }

        $drivers->approve($regid, $adminId);

        return $this->respond(['result' => true, 'message' => 'Driver approved successfully']);
    }

    /** /api/driver/admin/assignable-orders?filter=unassigned|assigned|all&search=&page= */
    public function assignableOrders(Request $request, DriverManagementService $drivers): JsonResponse
    {
        $filter = in_array($request->input('filter'), ['unassigned', 'assigned', 'all'], true) ? $request->input('filter') : 'unassigned';
        $page = $drivers->assignableOrdersQuery($filter, $request->input('search'))->paginate(20);

        return $this->respond([
            'result'       => true,
            'message'      => 'success',
            'orders'       => $this->rows($page->items()),
            'count'        => $page->total(),
            'current_page' => $page->currentPage(),
            'last_page'    => $page->lastPage(),
        ]);
    }

    /** /api/driver/admin/eligible-drivers?search= — approved, non-suspended drivers */
    public function eligibleDrivers(Request $request, DriverManagementService $drivers): JsonResponse
    {
        $rows = $this->rows($drivers->eligibleDriversQuery($request->input('search'))->limit(50)->get());

        return $this->respond(['result' => true, 'message' => 'success', 'drivers' => $rows, 'count' => count($rows)]);
    }

    /** POST /api/driver/admin/assign-order — order_id, regid, expected_rider_id (0 when unassigned) */
    public function assignOrder(Request $request, DriverManagementService $drivers): JsonResponse
    {
        $orderId = (string) $this->requireFilled($request, 'order_id');
        $regid = (int) $this->requireFilled($request, 'regid');
        if (! $request->exists('expected_rider_id') || ! is_numeric($request->input('expected_rider_id'))) {
            throw new ApiException('expected_rider_id is required (the current rider_id, 0 when unassigned)');
        }

        $result = $drivers->assign($orderId, $regid, (int) $request->input('expected_rider_id'));

        return $this->respond([
            'result'  => true,
            'message' => $result['reassigned'] ? 'Driver changed' : 'Driver assigned',
        ] + $result);
    }

    /** POST /api/driver/admin/toggle-driver-status — regid; flips status between 'active' and 'deactive' */
    public function toggleStatus(Request $request, DriverManagementService $drivers): JsonResponse
    {
        $next = $drivers->toggleAccountStatus((int) $this->requireFilled($request, 'regid'));

        return $this->respond(['result' => true, 'message' => 'Driver status updated to '.$next, 'status' => $next]);
    }

    private function verificationStatus($regid): ?string
    {
        $driver = DB::table('driver_registration')->where('id', $regid)->first(['verification_status']);
        if (! $driver) {
            throw new ApiException('Driver not found');
        }

        return $driver->verification_status;
    }

    private function withoutPasswords($drivers): array
    {
        return $drivers->map(function ($row) {
            $row = (array) $row;
            unset($row['password']);

            return $row;
        })->all();
    }
}
