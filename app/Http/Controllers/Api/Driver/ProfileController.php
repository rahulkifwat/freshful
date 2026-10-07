<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\ApiException;
use App\Support\DriverJwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends DriverController
{
    private const DOCUMENT_FIELDS = [
        'aadhaar_card'    => ['aadhaar_card_front', 'aadhaar_card_back'],
        'driving_license' => ['driving_license_front', 'driving_license_back'],
        'pan_card'        => ['pan_card_front', 'pan_card_back'],
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const MAX_UPLOAD_BYTES = 50 * 1024 * 1024;

    /**
     * /api/driver/driver/validate-token — was api/driver/driver/validate-token.php
     * Needs "lat" and "lng" request headers; assigns the nearest active hub and returns a fresh token.
     */
    public function validateToken(Request $request): JsonResponse
    {
        $regid = $this->driverId($request);

        // delivery_boy.regid is a varchar holding mixed values; compare as a string like legacy did.
        DB::table('delivery_boy')->where('regid', (string) $regid)->update(['hub_id' => $this->nearestHubId($request)]);

        $driver = DB::table('driver_registration')->where('id', $regid)->first();
        if (! $driver) {
            throw new ApiException('Driver not found');
        }

        return $this->respond([
            'result'  => true,
            'message' => 'Token valid',
            'token'   => DriverJwt::encode(['id' => $driver->id, 'phone' => $driver->phone, 'role' => 'driver']),
            'user'    => $this->driverWithRelations($driver),
        ]);
    }

    /** POST /api/driver/driver/personal-information — was api/driver/driver/personal-information.php (now needs the driver token) */
    public function personalInformation(Request $request): JsonResponse
    {
        $regid = $this->ownDriverId($request);

        if (! DB::table('driver_registration')->where('id', $regid)->exists()) {
            throw new ApiException('Driver not found');
        }

        $fields = ['name', 'father_name', 'dob', 'phone', 'blood_group', 'emergency_name',
            'emergency_relationship', 'emergency_phone', 'referral_code', 'address', 'language'];
        $save = [];
        foreach ($fields as $field) {
            $save[$field] = (string) $request->input($field, '');
        }
        // dob is a nullable DATE; '' is rejected in strict mode.
        $save['dob'] = $save['dob'] !== '' ? $save['dob'] : null;

        $photo = $request->file('profile_photo');
        if ($photo && $photo->isValid()) {
            $save['profile_photo'] = $this->storeImage($photo, 'profile_photos', Str::random(5).time(), 'profile_photo');
        }

        if (DB::table('driver_profile')->where('regid', $regid)->exists()) {
            DB::table('driver_profile')->where('regid', $regid)->update($save);
            $message = 'Profile updated successfully';
        } else {
            DB::table('driver_profile')->insert($save + ['regid' => $regid, 'profile_photo' => '']);
            $message = 'Profile created successfully';
        }

        return $this->respond(['result' => true, 'message' => $message]);
    }

    /** GET /api/driver/driver/bank-details */
    public function bankDetails(Request $request): JsonResponse
    {
        return $this->respond([
            'result'       => true,
            'message'      => 'success',
            'bank_details' => $this->firstRow('driver_bank_details', $this->driverId($request)),
        ]);
    }

    /** POST /api/driver/driver/bank-details — always the token's driver */
    public function saveBankDetails(Request $request): JsonResponse
    {
        $regid = $this->ownDriverId($request);

        $save = [];
        foreach (['account_holder_name', 'account_number', 'ifsc_code', 'bank_name', 'branch_name'] as $field) {
            $save[$field] = (string) $request->input($field, '');
        }

        if (DB::table('driver_bank_details')->where('regid', $regid)->exists()) {
            DB::table('driver_bank_details')->where('regid', $regid)->update($save);
            $message = 'Bank details updated successfully';
        } else {
            DB::table('driver_bank_details')->insert($save + ['regid' => $regid]);
            $message = 'Bank details added successfully';
        }

        return $this->respond(['result' => true, 'message' => $message]);
    }

    /** GET /api/driver/driver/documents */
    public function documents(Request $request): JsonResponse
    {
        return $this->respond([
            'result'    => true,
            'message'   => 'success',
            'documents' => $this->firstRow('driver_identity_documents', $this->driverId($request)),
        ]);
    }

    /**
     * POST /api/driver/driver/documents (multipart). Any of the six sides can be sent;
     * a document's *_status becomes "action" once both its sides exist.
     */
    public function saveDocuments(Request $request): JsonResponse
    {
        $regid = $this->driverId($request);
        $existing = $this->firstRow('driver_identity_documents', $regid);

        $uploaded = [];
        $oldFiles = [];
        foreach (array_merge(...array_values(self::DOCUMENT_FIELDS)) as $field) {
            $file = $request->file($field);
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $name = $field.'_'.time().'_'.random_int(100000000, 999999999).'_'.$regid;
            $uploaded[$field] = $this->storeImage($file, 'documents', $name, $field);
            if (! empty($existing[$field])) {
                $oldFiles[] = $existing[$field];
            }
        }

        if (! $uploaded) {
            throw new ApiException('No documents uploaded');
        }

        $save = $uploaded;
        foreach (self::DOCUMENT_FIELDS as $doc => [$front, $back]) {
            if (! empty($uploaded[$front] ?? $existing[$front] ?? null) && ! empty($uploaded[$back] ?? $existing[$back] ?? null)) {
                $save[$doc.'_status'] = 'action';
            }
        }

        if ($existing) {
            DB::table('driver_identity_documents')->where('regid', $regid)->update($save);
        } else {
            // Columns are NOT NULL without defaults; legacy relied on MySQL filling ''.
            $blank = array_fill_keys(array_merge(...array_values(self::DOCUMENT_FIELDS)), '');
            foreach (array_keys(self::DOCUMENT_FIELDS) as $doc) {
                $blank[$doc.'_status'] = '';
            }
            DB::table('driver_identity_documents')->insert($save + ['regid' => $regid] + $blank);
        }

        foreach ($oldFiles as $old) {
            $path = public_path('uploads/driver/documents/'.basename($old));
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return $this->respond([
            'result'    => true,
            'message'   => 'Documents uploaded successfully',
            'documents' => $this->firstRow('driver_identity_documents', $regid),
        ]);
    }

    /** GET /api/driver/driver/vehical */
    public function vehicles(Request $request): JsonResponse
    {
        return $this->respond([
            'result'   => true,
            'message'  => 'success',
            'vehicles' => $this->rows(DB::table('driver_vehicles')->where('regid', $this->driverId($request))->get()),
        ]);
    }

    /** POST /api/driver/driver/vehical — always the token's driver (legacy took any regid without a token) */
    public function saveVehicle(Request $request): JsonResponse
    {
        $regid = $this->ownDriverId($request);
        $save = [
            'vehicle_type'   => (string) $request->input('vehicle_type', ''),
            'vehicle_number' => (string) $request->input('vehicle_number', ''),
        ];

        if (DB::table('driver_vehicles')->where('regid', $regid)->exists()) {
            DB::table('driver_vehicles')->where('regid', $regid)->update($save);
            $message = 'Vehicle updated successfully';
        } else {
            DB::table('driver_vehicles')->insert($save + ['regid' => $regid, 'created_at' => $this->now()]);
            $message = 'Vehicle added successfully';
        }

        return $this->respond(['result' => true, 'message' => $message]);
    }

    /** POST /api/driver/driver/verification — driver submits for admin approval */
    public function submitVerification(Request $request): JsonResponse
    {
        $regid = $this->driverId($request);

        $driver = DB::table('driver_registration')->where('id', $regid)->first(['verification_status']);
        if (! $driver) {
            throw new ApiException('Driver not found');
        }
        $status = $driver->verification_status;
        if ($status === 'approved') {
            throw new ApiException('Driver already approved');
        }
        if ($status === 'pending_admin_approval') {
            throw new ApiException('Verification already submitted, pending admin approval');
        }

        if (! DB::table('driver_vehicles')->where('regid', $regid)->exists()) {
            throw new ApiException('Vehicle details are required');
        }

        $bank = $this->firstRow('driver_bank_details', $regid);
        if (! $bank) {
            throw new ApiException('Bank details are required');
        }
        foreach (['account_holder_name', 'account_number', 'ifsc_code', 'bank_name', 'branch_name'] as $field) {
            if (empty($bank[$field])) {
                throw new ApiException('All bank details fields must be filled');
            }
        }

        $docs = $this->firstRow('driver_identity_documents', $regid);
        if (! $docs) {
            throw new ApiException('Documents are required');
        }
        $labels = ['aadhaar_card' => 'Aadhaar card', 'driving_license' => 'Driving License', 'pan_card' => 'PAN card'];
        foreach (self::DOCUMENT_FIELDS as $doc => [$front, $back]) {
            if (empty($docs[$front]) || empty($docs[$back])) {
                throw new ApiException("Both sides of {$labels[$doc]} are required");
            }
        }

        DB::table('driver_registration')->where('id', $regid)
            ->update(['verification_status' => 'pending_admin_approval', 'verified_at' => $this->now()]);

        return $this->respond(['result' => true, 'message' => 'Verification submitted successfully. Pending admin approval.']);
    }

    /** Legacy setHub(): nearest active hub by the lat/lng headers (km), 0 when none. */
    private function nearestHubId(Request $request): int
    {
        if (! $request->hasHeader('lat') || ! $request->hasHeader('lng')) {
            throw new ApiException('Latitude and Longitude required');
        }
        $lat = (float) $request->header('lat');
        $lng = (float) $request->header('lng');

        $hub = DB::selectOne(
            "SELECT id FROM (
                SELECT id, (6371 * acos(cos(radians(?)) * cos(radians(hub_lat)) * cos(radians(hub_lng) - radians(?))
                    + sin(radians(?)) * sin(radians(hub_lat)))) AS distance
                FROM hubs WHERE status = 'active'
             ) h WHERE distance IS NOT NULL ORDER BY distance ASC LIMIT 1",
            [$lat, $lng, $lat]
        );

        return $hub ? (int) $hub->id : 0;
    }

    private function storeImage($file, string $folder, string $name, string $field): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            throw new ApiException("Invalid format for $field. Allowed: jpg, jpeg, png, webp");
        }
        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw new ApiException("File too large for $field");
        }

        $dir = public_path('uploads/driver/'.$folder);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file->move($dir, "$name.$ext");

        return "$name.$ext";
    }
}
