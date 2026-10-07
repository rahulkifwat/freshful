<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\ApiException;
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
    public function approval(Request $request): JsonResponse
    {
        $regid = $this->requireFilled($request, 'regid');
        $adminId = $request->user()->id;
        if (! $request->exists('approve')) {
            throw new ApiException('approve (true/false) is required');
        }

        if ($this->verificationStatus($regid) !== 'pending_admin_approval') {
            throw new ApiException('Driver is not pending approval');
        }

        $now = $this->now();

        if (! filter_var($request->input('approve'), FILTER_VALIDATE_BOOLEAN)) {
            DB::table('driver_registration')->where('id', $regid)->update([
                'verification_status' => 'rejected',
                'rejection_reason'    => (string) $request->input('rejection_reason', ''),
                'approved_by'         => $adminId,
                'approved_at'         => $now,
            ]);

            return $this->respond(['result' => true, 'message' => 'Driver rejected']);
        }

        DB::transaction(function () use ($regid, $adminId, $now) {
            DB::table('driver_registration')->where('id', $regid)
                ->update(['verification_status' => 'approved', 'approved_by' => $adminId, 'approved_at' => $now]);

            if (! DB::table('driver_wallet')->where('regid', $regid)->exists()) {
                DB::table('driver_wallet')->insert([
                    'regid' => $regid, 'balance' => 0, 'currency' => 'INR', 'status' => 'active',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });

        return $this->respond(['result' => true, 'message' => 'Driver approved successfully']);
    }

    /** POST /api/driver/admin/toggle-driver-status — regid; flips Activate <-> Deactivate */
    public function toggleStatus(Request $request): JsonResponse
    {
        $regid = $this->requireFilled($request, 'regid');

        $driver = DB::table('driver_registration')->where('id', $regid)->first(['status']);
        if (! $driver) {
            throw new ApiException('Driver not found');
        }

        $current = strtolower((string) $driver->status);
        $next = in_array($current, ['deactivate', '0'], true) ? 'Activate'
            : (in_array($current, ['activate', 'active', '1'], true) ? 'Deactivate' : 'Activate');

        DB::table('driver_registration')->where('id', $regid)->update(['status' => $next]);

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
