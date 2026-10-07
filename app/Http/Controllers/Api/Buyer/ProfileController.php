<?php

namespace App\Http\Controllers\Api\Buyer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends BuyerController
{
    /** /api/get_profile — was api/get_profile.php */
    public function show(Request $request): JsonResponse
    {
        $buyer = $this->buyerRows($this->ownBuyerId($request, 'id'));

        return $this->respond($buyer
            ? ['result' => true, 'message' => 'data found', 'data' => $buyer]
            : ['result' => false, 'message' => 'Data not found', 'data' => '']);
    }

    /** /api/update_profile — was api/update_profile.php */
    public function update(Request $request): JsonResponse
    {
        $id = $this->ownBuyerId($request, 'id');

        // Legacy wrote every request field to the row; protect auth/state columns.
        $values = $this->onlyColumns('buyers', $request->all(), ['id', 'otp', 'f_otp', 'cart_count', 'wallet_amount', 'auth_id', 'password']);
        $updated = $values && DB::table('buyers')->where('id', $id)->update($values);

        $buyer = $this->buyerRows($id);

        return $this->respond($updated
            ? ['data' => $buyer, 'result' => true, 'message' => 'success']
            : ['data' => $buyer, 'result' => false, 'message' => 'No Update ']);
    }

    /** Buyer row(s) for responses, without secrets. */
    private function buyerRows(int $id): array
    {
        return $this->rows(DB::table('buyers')->where('id', $id)->get()->map(function ($row) {
            unset($row->password, $row->otp, $row->f_otp);

            return $row;
        }));
    }
}
