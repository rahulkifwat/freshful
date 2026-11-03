<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Models\Buyer;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** The signed-in customer's own account (/api/app/me, /api/app/account/*). Needs the Bearer token. */
class AccountController extends AppController
{
    public function me(Request $request): JsonResponse
    {
        $row = $this->findBuyerById($this->ownBuyerId($request, 'buyer_id'));
        if (! $row) {
            return $this->fail('Account not found.', 'not_found');
        }

        return $this->ok([
            'buyer'         => $this->buyerPayload($row),
            'needs_profile' => $this->needsProfile($row),
            'has_password'  => (string) $row->password !== '',
        ]);
    }

    /** First-time details after the phone is verified: name, e-mail, optional gender. */
    public function complete(Request $request): JsonResponse
    {
        $id = $this->ownBuyerId($request, 'buyer_id');

        $name = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $request->input('name', ''))));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return $this->fail('Please enter your name.', 'invalid_name');
        }

        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email === '' || strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->fail('Please enter a valid e-mail address.', 'invalid_email');
        }
        if (DB::table('buyers')->where('email', $email)->where('id', '!=', $id)->exists()) {
            return $this->fail('This e-mail is already used by another account.', 'email_taken');
        }

        $gender = (string) $request->input('gender', '');
        $gender = in_array($gender, ['Male', 'Female'], true) ? $gender : '';

        try {
            DB::table('buyers')->where('id', $id)->update(['name' => $name, 'email' => $email, 'gender' => $gender, 'updated_at' => now()]);
        } catch (QueryException $e) {
            // The buyers table uses an older character set; letters outside it are refused by the database.
            report($e);

            return $this->fail('Please type your name using English letters.', 'name_unsupported');
        }

        $row = $this->findBuyerById($id);

        return $this->ok(['buyer' => $this->buyerPayload($row), 'needs_profile' => $this->needsProfile($row)], 'Profile saved');
    }

    /** Optional: lets the customer also sign in with phone + password. */
    public function setPassword(Request $request): JsonResponse
    {
        $id = $this->ownBuyerId($request, 'buyer_id');
        $password = (string) $request->input('password', '');

        if (strlen($password) < 8 || strlen($password) > 72) {
            return $this->fail('Password must be 8 to 72 characters.', 'invalid_password');
        }

        DB::table('buyers')->where('id', $id)->update(['password' => Hash::make($password), 'updated_at' => now()]);

        return $this->ok([], 'Password saved');
    }

    /**
     * Account deletion (required by the App Store). Needs confirm=DELETE.
     * The profile is wiped and the account closed; order, address and payment records stay for accounting.
     * Refused while an order is open or wallet money is left, so nothing is lost by accident.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $id = $this->ownBuyerId($request, 'buyer_id');

        if ((string) $request->input('confirm', '') !== 'DELETE') {
            return $this->fail('Please confirm the deletion.', 'confirm_required');
        }

        $row = $this->findBuyerById($id);
        if (! $row) {
            return $this->fail('Account not found.', 'not_found');
        }

        // An empty status counts as open too (NOT IN ignores NULL rows).
        $openOrder = DB::table('orders')
            ->where('buyer_id', $id)
            ->where(function ($q) {
                $q->whereNull('order_status')->orWhereNotIn('order_status', ['Order Delivered', 'Order Cancel']);
            })
            ->exists();
        if ($openOrder) {
            return $this->fail('You have an order in progress. Please delete your account after it is delivered or cancelled.', 'order_in_progress');
        }

        if ((float) ($row->wallet_amount ?? 0) > 0) {
            return $this->fail('Your wallet still has money in it. Please use it or contact Freshful support before deleting your account.', 'wallet_not_empty');
        }

        DB::transaction(function () use ($id) {
            DB::table('buyers')->where('id', $id)->update([
                'status'     => 'disabled',
                'name'       => 'Deleted customer',
                'email'      => '',
                'password'   => '',
                'otp'        => '',
                'f_otp'      => '0',
                'fcm_id'     => null,
                'auth_id'    => null,
                'provider'   => null,
                'regId'      => null,
                'phone'      => 'DEL' . $id,   // frees the number for a fresh sign-up later
                'cart_count' => '0',
                'updated_at' => now(),
            ]);
            DB::table('cart')->where('buyer_id', $id)->delete();
            DB::table('wishlist')->where('buyer_id', $id)->delete();
            Buyer::find($id)?->tokens()->delete();
        });

        return $this->ok([], 'Your account has been deleted.');
    }
}
