<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class DriverController extends ApiController
{
    /** driver_registration.id from the JWT (set by the driver.jwt middleware). */
    protected function driverId(Request $request): mixed
    {
        return $request->attributes->get('driver')['id'];
    }

    /**
     * The token's driver id for write endpoints. Old app builds still send regid;
     * it's accepted only when it matches, so nobody can write to another driver.
     */
    protected function ownDriverId(Request $request): mixed
    {
        $regid = $this->driverId($request);

        if ($request->filled('regid') && (string) $request->input('regid') !== (string) $regid) {
            throw new ApiException('regid does not match the logged-in driver');
        }

        return $regid;
    }

    /** driver_registration row with its profile and wallet, minus the password. */
    protected function driverWithRelations(object $driver): array
    {
        $row = (array) $driver;
        unset($row['password']);
        $row['profile'] = $this->firstRow('driver_profile', $driver->id);
        $row['wallet'] = $this->firstRow('driver_wallet', $driver->id);

        return $row;
    }

    protected function firstRow(string $table, $regid): ?array
    {
        $row = DB::table($table)->where('regid', $regid)->first();

        return $row ? (array) $row : null;
    }
}
