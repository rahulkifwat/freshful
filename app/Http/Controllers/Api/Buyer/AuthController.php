<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Exceptions\ApiException;
use App\Models\Buyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AuthController extends BuyerController
{
    /** Buyers log in by SMS OTP, so keep sessions long. */
    private const TOKEN_TTL_DAYS = 90;

    /** POST /api/login — was api/login.php */
    public function login(Request $request): JsonResponse
    {
        $phone  = (string) $request->input('phone', '');
        $fcmId  = (string) $request->input('fcm_id', '');
        $json   = ['result' => false, 'message' => 'something went wrong'];
        $buyerId = null;

        $buyer = DB::table('buyers')->where('phone', $phone)->first();

        if ($buyer) {
            $otp = '';
            for ($i = 0; $i < 5; $i++) {
                $otp .= random_int(0, 9);
            }

            if ($this->sendOtpSms($phone, $otp)) {
                $buyerId = $buyer->id;
                DB::table('buyers')->where('id', $buyerId)->update(['otp' => $otp, 'fcm_id' => $fcmId]);
                $json['message'] = 'OTP sent successfully';
            } else {
                $json['message'] = 'failed to send OTP pleasetry again';
            }
        } else {
            $json['message'] = 'mobile number not register';
        }

        $json['data'][] = ['id' => $buyerId, 'phone' => $phone, 'fcm_id' => $fcmId];
        $json['result'] = true;

        return $this->respond($json);
    }

    /** POST /api/otp — was api/otp.php */
    public function otp(Request $request): JsonResponse
    {
        $this->requireFields($request, ['otp', 'phone'], 'otp,phone,password(o) is required');

        $buyer = Buyer::where('phone', $request->input('phone'))->first();

        if (! $buyer) {
            throw new ApiException('Data not found');
        }

        if ((string) $buyer->otp === '' || ! hash_equals((string) $buyer->otp, (string) $request->input('otp'))) {
            throw new ApiException('Otp not matched');
        }

        // One-time: clear it so the same OTP can't be replayed for another token.
        DB::table('buyers')->where('id', $buyer->id)->update(['otp' => '']);

        $token = $buyer->createToken('buyer-app', ['buyer'], now()->addDays(self::TOKEN_TTL_DAYS));

        $row = (array) DB::table('buyers')->where('id', $buyer->id)->first();
        unset($row['password'], $row['otp'], $row['f_otp']);

        return $this->respond([
            'result'     => true,
            'message'    => 'Otp matched',
            'data'       => [$row],
            'token'      => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toDateTimeString(),
        ]);
    }

    /** POST /api/logout — revokes the buyer token used for this request */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respond(['result' => true, 'message' => 'Logged out']);
    }

    private function sendOtpSms(string $phone, string $otp): bool
    {
        $config = config('services.bhashsms');
        $text = "Dear Customer, Your One Time Password for registration is $otp, use this code to validate your login. Pls Do Not Share this OTP with anyone.Freshful";

        try {
            $response = Http::asForm()->timeout(15)->post($config['url'], [
                'user'     => $config['user'],
                'pass'     => $config['pass'],
                'sender'   => $config['sender'],
                'phone'    => $phone,
                'text'     => $text,
                'priority' => 'ndnd',
                'stype'    => 'normal',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        // Legacy treated any non-empty body from the gateway as success.
        return $response->body() !== '';
    }
}
