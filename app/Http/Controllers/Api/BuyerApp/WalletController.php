<?php

namespace App\Http\Controllers\Api\BuyerApp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GET wallet?page=   balance and statement (newest first, 20 per page), read from buyer_wallet_ledger.
 * Money is never changed here; top-ups stay on the website for now and orders / refunds write the ledger themselves.
 */
class WalletController extends AppController
{
    private const PER_PAGE = 20;

    public function show(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $page = max(1, (int) $request->input('page', 1));
        $balance = round((float) DB::table('buyers')->where('id', $buyerId)->value('wallet_amount'), 2);

        $rows = DB::table('buyer_wallet_ledger')->where('buyer_id', $buyerId)->orderByDesc('id')->get(['id', 'event_type', 'amount', 'balance_after', 'description', 'reference', 'created_at'])->all();
        $slice = array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        $tx = [];
        foreach ($slice as $r) {
            $tx[] = ['id' => (int) $r->id, 'type' => (string) $r->event_type, 'amount' => round((float) $r->amount, 2), 'balance_after' => round((float) $r->balance_after, 2),
                'description' => (string) $r->description, 'reference' => (string) $r->reference, 'at' => (string) $r->created_at];
        }

        return $this->ok(['balance' => $balance, 'page' => $page, 'has_more' => count($rows) > $page * self::PER_PAGE, 'transactions' => $tx]);
    }
}
