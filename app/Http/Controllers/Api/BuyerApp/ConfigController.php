<?php

namespace App\Http\Controllers\Api\BuyerApp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public app settings: forced-update check and maintenance notice. GET /api/app/config?platform=android&version=2.0.0 */
class ConfigController extends AppController
{
    public function show(Request $request): JsonResponse
    {
        $platform = $request->input('platform') === 'ios' ? 'ios' : 'android';
        $version = trim((string) $request->input('version', ''));
        $config = config('buyer_app');

        $min = (string) $config['min_version'][$platform];
        $latest = (string) $config['latest_version'][$platform];

        return $this->ok([
            'platform'         => $platform,
            'update_required'  => $version !== '' && version_compare($version, $min, '<'),
            'update_available' => $version !== '' && version_compare($version, $latest, '<'),
            'min_version'      => $min,
            'latest_version'   => $latest,
            'store_url'        => (string) $config['store_url'][$platform],
            'maintenance'      => [
                'on'      => (bool) $config['maintenance']['on'],
                'message' => (string) $config['maintenance']['message'],
            ],
        ]);
    }
}
