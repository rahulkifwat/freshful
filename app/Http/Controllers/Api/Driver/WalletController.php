<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WalletController extends DriverController
{
    private const EARNING_TYPES = ['credit', 'trip_earning', 'tip', 'admin_credit'];

    /** /api/driver/wallet/balance */
    public function balance(Request $request): JsonResponse
    {
        $wallet = DB::table('driver_wallet')->where('regid', $this->driverId($request))->first(['balance', 'currency', 'status']);
        if (! $wallet) {
            throw new ApiException('Wallet not found');
        }

        return $this->respond(['result' => true, 'message' => 'success', 'wallet' => (array) $wallet]);
    }

    /**
     * POST /api/driver/wallet/credit (also /wallet/create) — regid, amount, type(o), reference_id(o), description(o)
     * Admin token only, so drivers can't move money into any wallet.
     */
    public function credit(Request $request): JsonResponse
    {
        return $this->move($request, +1, 'credit', 'Wallet credited successfully');
    }

    /** POST /api/driver/wallet/debit — admin token only */
    public function debit(Request $request): JsonResponse
    {
        return $this->move($request, -1, 'debit', 'Wallet debited successfully');
    }

    /** /api/driver/wallet/transactions?filter=all|deposit|withdrawal|tip — grouped by day */
    public function transactions(Request $request): JsonResponse
    {
        $regid = $this->driverId($request);
        $txns = fn () => DB::table('driver_wallet_transactions')->where('regid', $regid);

        $list = $txns();
        match ($request->input('filter', 'all')) {
            'deposit'    => $list->whereIn('type', ['credit', 'deposit', 'trip_earning', 'tip', 'admin_credit']),
            'withdrawal' => $list->whereIn('type', ['debit', 'withdrawal_request', 'withdrawal_approved', 'withdrawal_rejected']),
            'tip'        => $list->where('type', 'tip'),
            default      => null,
        };

        $grouped = [];
        foreach ($list->orderByDesc('created_at')->get() as $txn) {
            $grouped[Carbon::parse($txn->created_at)->toDateString()][] = (array) $txn;
        }

        $weekStart = Carbon::now(self::TZ)->startOfWeek(Carbon::MONDAY)->toDateString();

        return $this->respond([
            'result'  => true,
            'message' => 'success',
            'summary' => [
                'week_earning' => $txns()->whereIn('type', self::EARNING_TYPES)->where('created_at', '>=', $weekStart)->sum('amount'),
                'deliveries'   => $txns()->where('type', 'trip_earning')->where('created_at', '>=', $weekStart)->count(),
                'pending'      => $txns()->where('type', 'withdrawal_request')->where('status', 'pending')->sum('amount'),
            ],
            'transactions' => $grouped,
        ]);
    }

    private function move(Request $request, int $sign, string $defaultType, string $message): JsonResponse
    {
        $regid = $this->requireFilled($request, 'regid');
        $amount = (float) $request->input('amount', 0);
        if ($amount <= 0) {
            throw new ApiException('Valid amount is required');
        }

        $balance = DB::transaction(function () use ($request, $regid, $amount, $sign, $defaultType) {
            // Row lock so concurrent credits/debits can't overwrite each other's balance.
            $wallet = DB::table('driver_wallet')->where('regid', $regid)->lockForUpdate()->first();
            if (! $wallet) {
                throw new ApiException('Wallet not found');
            }
            if ($sign < 0 && $wallet->balance < $amount) {
                throw new ApiException('Insufficient balance');
            }

            $balance = $wallet->balance + $sign * $amount;
            $now = $this->now();

            DB::table('driver_wallet')->where('id', $wallet->id)->update(['balance' => $balance, 'updated_at' => $now]);
            DB::table('driver_wallet_transactions')->insert([
                'wallet_id'    => $wallet->id,
                'regid'        => $regid,
                'type'         => $request->input('type', $defaultType),
                'amount'       => $amount,
                'reference_id' => (string) $request->input('reference_id', ''),
                'description'  => (string) $request->input('description', ''),
                'status'       => 'completed',
                'created_at'   => $now,
            ]);

            return $balance;
        });

        return $this->respond(['result' => true, 'message' => $message, 'balance' => $balance]);
    }
}
