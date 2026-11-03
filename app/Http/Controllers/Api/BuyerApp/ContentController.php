<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Shop\Latin1;
use App\Services\BuyerApp\Shop\OrderStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Text pages (about, terms, privacy, refund, shipping, FAQ), cancel reasons and contact us.
 * Banners are not here: the existing /api/banners and /api/get_app_banner_images keep serving them.
 */
class ContentController extends AppController
{
    /** public name => [title, policies.type] ; privacy has its own table and falls back to policies */
    private const PAGES = [
        'about'    => ['About Freshful', 'about'],
        'terms'    => ['Terms of Service', 'terms'],
        'privacy'  => ['Privacy Policy', 'privacy-policy'],
        'refund'   => ['Refund Policy', 'refund_ploicy'],   // spelled like this in the table
        'shipping' => ['Shipping Policy', 'shipping_policy'],
        'faq'      => ['FAQs', 'faq'],
    ];

    /** GET /api/app/content/pages */
    public function pages(): JsonResponse
    {
        $out = [];
        foreach (self::PAGES as $type => [$title]) {
            if ($this->html($type) !== null) {
                $out[] = ['type' => $type, 'title' => $title];
            }
        }

        return $this->ok(['pages' => $out]);
    }

    /** GET /api/app/content/pages/{type} -> html to show in the app */
    public function page(string $type): JsonResponse
    {
        if (! isset(self::PAGES[$type])) {
            return $this->fail('Page not found', 'page_not_found');
        }
        $html = $this->html($type);
        if ($html === null) {
            return $this->fail('Page not found', 'page_not_found');
        }

        return $this->ok(['type' => $type, 'title' => self::PAGES[$type][0], 'html' => $html]);
    }

    /** GET /api/app/content/cancel-reasons */
    public function cancelReasons(): JsonResponse
    {
        $list = [];
        foreach (OrderStage::CANCEL_REASONS as $id => $reason) {
            $list[] = ['id' => $id, 'reason' => $reason];
        }

        return $this->ok(['reasons' => $list]);
    }

    /** POST /api/app/contact {message, name?, email?} - name, email and phone default to the customer's own */
    public function contact(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $buyer = $this->findBuyerById($buyerId);

        $message = trim((string) $request->input('message', ''));
        if (mb_strlen($message) < 5) {
            return $this->fail('Please write your message', 'message_required');
        }
        if (mb_strlen($message) > 250) {
            return $this->fail('Your message is too long (250 characters at most)', 'message_too_long');
        }

        $name = trim((string) $request->input('name', '')) ?: trim((string) ($buyer->name ?? ''));
        $email = trim((string) $request->input('email', '')) ?: trim((string) ($buyer->email ?? ''));
        $phone = trim((string) ($buyer->phone ?? ''));

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Please enter a valid e-mail address', 'invalid_email');
        }
        if (mb_strlen($name) > 100 || mb_strlen($email) > 100) {
            return $this->fail('Name or e-mail is too long', 'too_long');
        }
        foreach ([$name, $email, $message] as $text) {
            if (! Latin1::canStore($text)) {
                return $this->fail('Please remove emojis or special characters from your message', 'text_unsupported');
            }
        }

        DB::table('contact_us')->insert([
            'fname' => $name,
            'lname' => '',
            'email' => $email,
            'phone' => $phone,
            'query' => $message,
        ]);

        return $this->ok([], 'Thank you. We will contact you soon.');
    }

    /** HTML of one page, or null when there is none (or it is switched off). */
    private function html(string $type): ?string
    {
        if ($type === 'privacy') {
            $row = DB::table('privacy_policy')->orderByDesc('id')->first();
            $text = $row ? trim((string) $row->description) : '';
            if ($text !== '') {
                return $text;
            }
        }

        $row = DB::table('policies')
            ->where('type', self::PAGES[$type][1])
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->orderByDesc('id')
            ->first();
        $text = $row ? trim((string) $row->content) : '';

        return $text !== '' ? $text : null;
    }
}
