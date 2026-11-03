<?php

namespace App\Http\Controllers\PosConsole;

use Illuminate\Routing\Controller;
use App\Services\PosConsole\PosRiderFlow;
use Illuminate\Http\Request;

class PosRiderFlowController extends Controller
{
    private function actor(Request $r): array
    {
        return (array) $r->attributes->get('pos_actor', []);
    }

    private function flow(Request $r): PosRiderFlow
    {
        return new PosRiderFlow((int) ($this->actor($r)['hub_id'] ?? 0));
    }

    private function json(array $data, int $code = 200)
    {
        return response()->json($data, $code)->header('Cache-Control', 'no-store');
    }

    public function assign(Request $r, string $orderId)
    {
        $v = $r->validate(['rider_id' => 'required|integer|min:1']);
        $res = $this->flow($r)->assign($orderId, (int) $v['rider_id'], $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function assignments(Request $r)
    {
        return $this->json(['ok' => true, 'map' => (object) $this->flow($r)->assignments()]);
    }

    public function handover(Request $r, int $riderId)
    {
        $v = $r->validate(['orders' => 'required|array|min:1|max:60', 'orders.*' => 'string|max:40']);
        $res = $this->flow($r)->handover($riderId, $v['orders'], $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function deliverMany(Request $r)
    {
        $v = $r->validate(['orders' => 'required|array|min:1|max:60', 'orders.*' => 'string|max:40', 'note' => 'nullable|string|max:200']);
        $res = $this->flow($r)->deliverMany($v['orders'], $v['note'] ?? null, $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }
}
