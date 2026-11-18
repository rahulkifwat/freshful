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
    private const MOVE_TYPES    = ['credit', 'debit', 'admin_credit', 'admin_debit', 'tip', 'penalty', 'trip_earning'];

    /** GET /api/driver/wallet/balance */
    public function balance(Request $request): JsonResponse
    {
        $regid = $this->driverId($request);

        // Wallet abhi na bana ho (naya driver) to 0 balance dikhao
        $wallet = DB::table('driver_wallet')->where('regid', $regid)->first(['balance', 'currency', 'status'])
            ?? (object) ['balance' => '0.00', 'currency' => 'INR', 'status' => 'active'];

        $monthStart = Carbon::now(self::TZ)->startOfMonth()->toDateTimeString();
        $monthEnd   = Carbon::now(self::TZ)->startOfMonth()->addMonth()->toDateTimeString();

        $delivered = DB::table('orders')
            ->where('rider_id', $regid)
            ->where('order_status', 'Order Delivered')
            ->where('delivery_complete_time', '>=', $monthStart)
            ->where('delivery_complete_time', '<', $monthEnd)
            ->distinct()
            ->count('order_id');

        return $this->respond([
            'result'  => true,
            'message' => 'success',
            'wallet'  => (array) $wallet,
            'bonus'   => [
                'month_delivered'  => $delivered,
                'threshold'        => 100,
                'per_order'        => 10,
                'orders_to_unlock' => max(0, 101 - $delivered),
            ],
        ]);
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

    /**
     * GET /api/driver/wallet/transactions?filter=all|deposit|withdrawal|tip&limit=100&offset=0
     * Day ke hisaab se grouped.
     */
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

        $limit  = min(max((int) $request->input('limit', 100), 1), 500);
        $offset = max((int) $request->input('offset', 0), 0);

        $grouped = [];
        $rows = $list->orderByDesc('created_at')->orderByDesc('id')->offset($offset)->limit($limit)->get();
        foreach ($rows as $txn) {
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
            'limit'        => $limit,
            'offset'       => $offset,
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

        $type = (string) $request->input('type', $defaultType);
        if (! in_array($type, self::MOVE_TYPES, true)) {
            throw new ApiException('Invalid type');
        }

        $amountStr = number_format($amount, 2, '.', '');

        $balance = DB::transaction(function () use ($request, $regid, $amountStr, $sign, $type) {
            // Row lock so concurrent credits/debits can't overwrite each other's balance.
            $wallet = DB::table('driver_wallet')->where('regid', $regid)->lockForUpdate()->first();
            if (! $wallet) {
                throw new ApiException('Wallet not found');
            }
            if ($wallet->status !== 'active') {
                throw new ApiException('Wallet is blocked');
            }
            if ($sign < 0 && bccomp((string) $wallet->balance, $amountStr, 2) < 0) {
                throw new ApiException('Insufficient balance');
            }

            $balance = $sign > 0
                ? bcadd((string) $wallet->balance, $amountStr, 2)
                : bcsub((string) $wallet->balance, $amountStr, 2);
            $now = $this->now();

            DB::table('driver_wallet')->where('id', $wallet->id)->update(['balance' => $balance, 'updated_at' => $now]);
            DB::table('driver_wallet_transactions')->insert([
                'wallet_id'    => $wallet->id,
                'regid'        => $regid,
                'type'         => $type,
                'amount'       => $amountStr,
                'reference_id' => (string) $request->input('reference_id', ''),
                'description'  => (string) $request->input('description', ''),
                'status'       => 'completed',
                'created_at'   => $now,
            ]);

            return $balance;
        });

        return $this->respond(['result' => true, 'message' => $message, 'balance' => (float) $balance]);
    }
}