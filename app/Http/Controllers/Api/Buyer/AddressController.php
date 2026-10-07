<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends BuyerController
{
    /** /api/add_address — was api/add_address.php */
    public function store(Request $request): JsonResponse
    {
        $this->requireFields(
            $request,
            ['hub_id', 'user_lat', 'user_lng', 'phone', 'city', 'street_name', 'locality', 'landmark'],
            'hub_id,user_lat,user_lng,phone,city,street_name,locality,landmark,name is required'
        );

        $values = ['buyer_id' => $this->ownBuyerId($request)] + $this->onlyColumns('buyer_address', $request->all());
        DB::table('buyer_address')->insert($values);

        return $this->respond(['result' => true, 'message' => 'address added successfully', 'address' => $values]);
    }

    /** /api/get_address — was api/get_address.php */
    public function index(Request $request): JsonResponse
    {
        $addresses = $this->rows(DB::table('buyer_address')->where('buyer_id', $this->ownBuyerId($request))->get());

        return $this->respond($addresses
            ? ['result' => true, 'message' => 'data found', 'data' => $addresses]
            : ['result' => false, 'message' => 'Data not found', 'data' => '']);
    }

    /** /api/update_address — was api/update_address.php */
    public function update(Request $request): JsonResponse
    {
        $this->requireFields(
            $request,
            ['address_id'],
            'address_id,user_lat(o),user_lng(o),phone(o),city(o),street_name(o),locality(o),landmark(o),name(o) is required'
        );

        $buyerId = $this->ownBuyerId($request);
        // buyer_id can't be changed: an address never moves to another buyer.
        $values = $this->onlyColumns('buyer_address', $request->except('address_id'), ['id', 'buyer_id']);

        if (! $values || ! $this->ownAddress($request, $buyerId)->update($values)) {
            throw new ApiException('Something Went Wrong');
        }

        return $this->respond(['result' => true, 'message' => 'address updated successfully', 'data' => $values]);
    }

    /** /api/delete_address — was api/delete_address.php */
    public function destroy(Request $request): JsonResponse
    {
        $this->requireFields($request, ['address_id'], 'address_id is required');

        if (! $this->ownAddress($request, $this->ownBuyerId($request))->delete()) {
            throw new ApiException('Address not found');
        }

        return $this->respond(['result' => true, 'message' => 'Address deleted successfully']);
    }

    private function ownAddress(Request $request, int $buyerId)
    {
        return DB::table('buyer_address')->where('id', $request->input('address_id'))->where('buyer_id', $buyerId);
    }
}
