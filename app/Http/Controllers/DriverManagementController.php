<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Services\DriverManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin panel: Driver Management (driver list, pending approvals, order assignment).
 * Routes live in the admin-session group of routes/web.php; business rules are in
 * DriverManagementService so the admin token API enforces exactly the same ones.
 */
class DriverManagementController extends Controller
{
    private const FILTERS = ['pending', 'approved', 'rejected', 'suspended', 'incomplete'];

    public function __construct(private DriverManagementService $drivers)
    {
    }

    public function index(Request $request)
    {
        return $this->listPage($request, in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : null, 'Driver List');
    }

    public function pending(Request $request)
    {
        return $this->listPage($request, 'pending', 'Pending Driver Approvals');
    }

    public function show(Request $request)
    {
        $details = $this->drivers->driverDetails((int) $request->query('id'));
        if (! $details) {
            return redirect()->route('admin.drivers.index')->with('error', 'Driver not found.');
        }

        $photo = $details['profile']->profile_photo ?? null;
        $details['photo_url'] = $this->uploadUrl('profile_photos', $photo);
        $details['document_urls'] = [];
        foreach (['aadhaar_card_front', 'aadhaar_card_back', 'driving_license_front', 'driving_license_back', 'pan_card_front', 'pan_card_back'] as $field) {
            $details['document_urls'][$field] = $this->uploadUrl('documents', $details['documents']->$field ?? null);
        }

        return view('admin.drivers.show', $details);
    }

    public function approve(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => 'required|integer|min:1']);
        $this->drivers->approve((int) $data['id'], $this->adminId());

        return response()->json(['result' => true, 'message' => 'Driver approved successfully']);
    }

    public function reject(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'     => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
        ]);
        $this->drivers->reject((int) $data['id'], $this->adminId(), (string) ($data['reason'] ?? ''));

        return response()->json(['result' => true, 'message' => 'Driver rejected']);
    }

    public function assignment(Request $request)
    {
        $filter = in_array($request->query('filter'), ['unassigned', 'assigned', 'all'], true) ? $request->query('filter') : 'unassigned';
        $orders = $this->drivers->assignableOrdersQuery($filter, $request->query('search'))->paginate(20)->withQueryString();

        return view('admin.drivers.assignment', [
            'orders'   => $orders,
            'filter'   => $filter,
            'statuses' => DriverManagementService::ASSIGNABLE_ORDER_STATUSES,
        ]);
    }

    /** JSON for the Assign Driver modal: approved, non-suspended drivers. */
    public function eligibleDrivers(Request $request): JsonResponse
    {
        $drivers = $this->drivers->eligibleDriversQuery($request->query('search'))->limit(50)->get()
            ->map(fn ($d) => [
                'id'             => $d->id,
                'name'           => $d->name ?: $d->email,
                'phone'          => $d->phone,
                'vehicle_type'   => $d->vehicle_type,
                'vehicle_number' => $d->vehicle_number,
                'status'         => $d->status,
                'open_orders'    => (int) $d->open_orders,
            ]);

        return response()->json(['result' => true, 'drivers' => $drivers]);
    }

    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'          => 'required|string|max:50',
            'driver_id'         => 'required|integer|min:1',
            'expected_rider_id' => 'required|integer|min:0',
        ]);

        $result = $this->drivers->assign($data['order_id'], (int) $data['driver_id'], (int) $data['expected_rider_id']);

        return response()->json([
            'result'  => true,
            'message' => ($result['reassigned'] ? 'Driver changed' : 'Driver assigned').' for order '.$result['order_id'],
        ] + $result);
    }

    private function listPage(Request $request, ?string $filter, string $title)
    {
        $drivers = $this->drivers->driversQuery($filter, $request->query('search'))->paginate(20)->withQueryString();
        $drivers->getCollection()->transform(function ($d) {
            $d->photo_url = $this->uploadUrl('profile_photos', $d->profile_photo);
            $d->suspended = $this->drivers->isSuspended($d->status);

            return $d;
        });

        return view('admin.drivers.index', compact('drivers', 'filter', 'title'));
    }

    private function adminId(): int
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            throw new ApiException('Unauthorized');
        }

        return (int) $admin->id;
    }

    /**
     * Driver uploads: files saved by the Laravel API are under public/uploads/driver;
     * older ones uploaded through the legacy api/ folder live on the main site.
     */
    private function uploadUrl(string $folder, ?string $file): ?string
    {
        if ($file === null || $file === '') {
            return null;
        }
        $file = basename($file);

        if (is_file(public_path("uploads/driver/$folder/$file"))) {
            return url("uploads/driver/$folder/$file");
        }

        return rtrim((string) config('services.legacy_uploads_url'), '/')."/driver/$folder/$file";
    }
}
