<?php

namespace App\Services\AdminConsole;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): the simple master screens, described once.
 * Each one mirrors an old PHP admin page (same table, same columns, same image folder), so the website and the app
 * read exactly what they read before. AdminRecords draws the list, the add / edit form and the on / off switch from this.
 *
 * Field types: text, textarea, html, number, money, date (empty saves "0" = no expiry, like the old promo form),
 * select (opts = fixed list, src = list from the database), image (folder under uploads/images), status (on / off switch),
 * csv (several values saved comma separated, like the old product filter parameters).
 * delete = true only for small lookup lists; business records are switched off, never deleted. Every change is in the change log.
 */
class AdminResources
{
    public static function all(): array
    {
        $status = ['t' => 'status', 'label' => 'Status'];
        return [
            /* ---------------- catalogue */
            'maincat' => ['table' => 'categories', 'title' => 'Main category', 'plural' => 'Main categories', 'module' => 'Catalogue',
                'help' => 'The top level of the shop (for example Meat, Seafood). Categories belong to a main category by its name, so renaming one here also renames it on its categories.',
                'fields' => ['name' => ['t' => 'text', 'label' => 'Name', 'req' => 1, 'max' => 100, 'unique' => 1], 'image' => ['t' => 'image', 'label' => 'Image', 'folder' => ''], 'status' => $status],
                'list' => ['image', 'name', 'status'], 'search' => ['name'], 'order' => ['id', 'asc'], 'hook' => 'maincat'],
            'category' => ['table' => 'category', 'title' => 'Category', 'plural' => 'Categories', 'module' => 'Catalogue',
                'help' => 'Shop categories under a main category. Lower rank shows first on the website and app.',
                'fields' => ['group_type' => ['t' => 'select', 'label' => 'Main category', 'src' => 'maincat_names', 'req' => 1], 'name' => ['t' => 'text', 'label' => 'Name', 'req' => 1, 'max' => 100],
                    'image' => ['t' => 'image', 'label' => 'Image', 'folder' => ''], 'circle_image' => ['t' => 'image', 'label' => 'Circle image (round icon)', 'folder' => ''],
                    'ranking' => ['t' => 'number', 'label' => 'Rank (order shown)', 'min' => 0, 'max' => 9999, 'auto_next' => 1], 'status' => $status],
                'list' => ['ranking', 'group_type', 'name', 'image', 'circle_image', 'status'], 'search' => ['name', 'group_type'], 'order' => ['ranking', 'asc']],
            'items' => ['table' => 'items', 'title' => 'Item', 'plural' => 'Items', 'module' => 'Catalogue',
                'help' => 'An item is the thing you sell (for example "Chicken Curry Cut"). Each product is one pack size of an item, and takes its name and photo from here.',
                'fields' => ['cat_id' => ['t' => 'select', 'label' => 'Category', 'src' => 'categories', 'req' => 1], 'item' => ['t' => 'text', 'label' => 'Item name', 'req' => 1, 'max' => 150],
                    'image' => ['t' => 'image', 'label' => 'Photo', 'folder' => 'products'], 'status' => $status],
                'list' => ['image', 'item', 'cat_id', 'status'], 'search' => ['item'], 'order' => ['id', 'desc'], 'hook' => 'items'],
            'units' => ['table' => 'product_units', 'title' => 'Product unit', 'plural' => 'Product units', 'module' => 'Catalogue',
                'help' => 'Units a pack can be measured in (Gram, Kg, Pcs, Dozen, Ltr...). Stock shows in kg for weight units and in pieces for count units.',
                'fields' => ['unit' => ['t' => 'text', 'label' => 'Unit', 'req' => 1, 'max' => 30, 'unique' => 1]], 'list' => ['unit'], 'search' => ['unit'], 'order' => ['unit', 'asc'], 'delete' => 'unit_unused'],
            'tags' => ['table' => 'product_tags', 'title' => 'Product tag', 'plural' => 'Product tags', 'module' => 'Catalogue',
                'help' => 'Tags group products on the home page and side slider (for example Bestseller, Ready to cook).',
                'fields' => ['tag' => ['t' => 'text', 'label' => 'Tag', 'req' => 1, 'max' => 60, 'unique' => 1], 'status' => $status], 'list' => ['tag', 'status'], 'search' => ['tag'], 'order' => ['id', 'asc']],
            'filters' => ['table' => 'product_filter', 'title' => 'Product filter', 'plural' => 'Product filters', 'module' => 'Catalogue',
                'help' => 'Extra choices shown on products of a category (for example Cut type: Curry cut, Boneless). Products pick their values when they are added or edited.',
                'fields' => ['main_cat_id' => ['t' => 'select', 'label' => 'Main category', 'src' => 'maincat_ids', 'req' => 1], 'subcat_id' => ['t' => 'select', 'label' => 'Category', 'src' => 'categories', 'req' => 1],
                    'property_name' => ['t' => 'text', 'label' => 'Property name', 'req' => 1, 'max' => 80], 'parameter' => ['t' => 'csv', 'label' => 'Values (one per line)', 'req' => 1, 'max' => 1000]],
                'list' => ['main_cat_id', 'subcat_id', 'property_name', 'parameter'], 'search' => ['property_name', 'parameter'], 'order' => ['id', 'asc'], 'delete' => true],

            /* ---------------- customers */
            'buyers' => ['table' => 'buyers', 'title' => 'Customer account', 'plural' => 'Customer accounts', 'module' => 'Customers',
                'help' => 'Customer login accounts. Switching an account off blocks it from ordering. Wallet money is changed only through wallet transactions, never here.',
                'fields' => ['name' => ['t' => 'text', 'label' => 'Name', 'req' => 1, 'max' => 100], 'email' => ['t' => 'email', 'label' => 'Email', 'max' => 150],
                    'phone' => ['t' => 'text', 'label' => 'Mobile', 'max' => 15, 'ro' => 1], 'wallet_amount' => ['t' => 'money', 'label' => 'Wallet', 'ro' => 1],
                    'referral_code' => ['t' => 'text', 'label' => 'Referral code', 'ro' => 1], 'date_added' => ['t' => 'text', 'label' => 'Joined', 'ro' => 1], 'status' => $status],
                'list' => ['name', 'phone', 'email', 'wallet_amount', 'date_added', 'status'], 'search' => ['name', 'phone', 'email'], 'order' => ['id', 'desc'], 'add' => false],

            /* ---------------- locations */
            'cities' => ['table' => 'cities', 'title' => 'City', 'plural' => 'Cities', 'module' => 'Locations',
                'help' => 'Cities you deliver in. Hubs belong to a city; the delivery charge is set per city.',
                'fields' => ['state_id' => ['t' => 'select', 'label' => 'State', 'src' => 'states_in', 'req' => 1], 'city' => ['t' => 'text', 'label' => 'City', 'req' => 1, 'max' => 80], 'status' => $status],
                'list' => ['city', 'state_id', 'status'], 'search' => ['city'], 'order' => ['city', 'asc']],
            'delivery' => ['table' => 'delivery_price', 'title' => 'Delivery charge', 'plural' => 'Delivery charges', 'module' => 'Locations',
                'help' => 'The delivery charge customers pay, per city. Checkout uses this amount (the server works it out; the customer cannot change it).',
                'fields' => ['city' => ['t' => 'select', 'label' => 'City', 'src' => 'cities', 'req' => 1, 'unique' => 1], 'price' => ['t' => 'money', 'label' => 'Delivery charge (Rs.)', 'req' => 1, 'min' => 0, 'max' => 1000]],
                'list' => ['city', 'price'], 'search' => [], 'order' => ['id', 'asc'], 'delete' => true, 'stamps' => 1],

            /* ---------------- marketing */
            'banners' => ['table' => 'banner_images', 'title' => 'Website banner', 'plural' => 'Website banners', 'module' => 'Marketing',
                'help' => 'Large banners on the website home page. Link = the page that opens when the banner is clicked.',
                'fields' => ['title' => ['t' => 'text', 'label' => 'Title', 'req' => 1, 'max' => 150], 'url' => ['t' => 'text', 'label' => 'Link (URL)', 'max' => 255], 'image' => ['t' => 'image', 'label' => 'Banner image', 'folder' => 'banners', 'req' => 1], 'status' => $status],
                'list' => ['image', 'title', 'url', 'status'], 'search' => ['title', 'url'], 'order' => ['id', 'desc']],
            'appbanners' => ['table' => 'app_banner_images', 'title' => 'App banner', 'plural' => 'App banners', 'module' => 'Marketing',
                'help' => 'Banners in the customer app.',
                'fields' => ['title' => ['t' => 'text', 'label' => 'Title', 'req' => 1, 'max' => 150], 'url' => ['t' => 'text', 'label' => 'Link (URL or screen)', 'max' => 255], 'image' => ['t' => 'image', 'label' => 'Banner image', 'folder' => 'banners', 'req' => 1], 'status' => $status],
                'list' => ['image', 'title', 'url', 'status'], 'search' => ['title', 'url'], 'order' => ['id', 'desc']],
            'slider' => ['table' => 'side_slider', 'title' => 'Side slider image', 'plural' => 'Side slider', 'module' => 'Marketing',
                'help' => 'Images in the website side slider, each opening the products with its tag.',
                'fields' => ['tag' => ['t' => 'select', 'label' => 'Product tag', 'src' => 'tags', 'req' => 1], 'image' => ['t' => 'image', 'label' => 'Image', 'folder' => '', 'req' => 1]],
                'list' => ['image', 'tag'], 'search' => ['tag'], 'order' => ['id', 'desc'], 'delete' => true],
            'offers' => ['table' => 'home_offers', 'title' => 'Home offer', 'plural' => 'Home offers', 'module' => 'Marketing',
                'help' => 'Offer cards on the home page.',
                'fields' => ['title' => ['t' => 'text', 'label' => 'Title', 'req' => 1, 'max' => 150], 'description' => ['t' => 'textarea', 'label' => 'Description', 'max' => 1000], 'image' => ['t' => 'image', 'label' => 'Image', 'folder' => 'offers'], 'status' => $status],
                'list' => ['image', 'title', 'description', 'status'], 'search' => ['title', 'description'], 'order' => ['id', 'desc'], 'stamp' => 'date_added'],
            'promos' => ['table' => 'home_promotions', 'title' => 'Promo code', 'plural' => 'Promo codes', 'module' => 'Marketing',
                'help' => 'Codes customers type at checkout. Discount = % of the order, capped at the maximum amount, only on orders above the minimum. Empty expiry = never expires.',
                'fields' => ['title' => ['t' => 'code', 'label' => 'Promo code', 'req' => 1, 'max' => 40, 'unique' => 1], 'user_eligible' => ['t' => 'select', 'label' => 'Who can use it', 'req' => 1, 'opts' => ['everyone' => 'Everyone', 'new user' => 'New customers only']],
                    'percentage' => ['t' => 'number', 'label' => 'Discount %', 'req' => 1, 'min' => 1, 'max' => 100, 'step' => '0.01'], 'min_amount' => ['t' => 'money', 'label' => 'Minimum order (Rs.)', 'req' => 1, 'min' => 0],
                    'max_amount' => ['t' => 'money', 'label' => 'Maximum discount (Rs.)', 'req' => 1, 'min' => 0], 'max_usage' => ['t' => 'number', 'label' => 'Uses per customer', 'min' => 0, 'max' => 100000],
                    'expiry' => ['t' => 'date', 'label' => 'Expiry date'], 'description' => ['t' => 'textarea', 'label' => 'Description', 'max' => 500], 'image' => ['t' => 'image', 'label' => 'Image', 'folder' => 'promotions']],
                'list' => ['image', 'title', 'user_eligible', 'percentage', 'min_amount', 'max_amount', 'max_usage', 'expiry', 'used'], 'search' => ['title', 'description'], 'order' => ['id', 'desc'], 'delete' => 'promo_unused', 'stamps' => 1, 'hook' => 'promos'],
            'coupons' => ['table' => 'coupons_and_deals', 'title' => 'Coupon / deal', 'plural' => 'Coupons & deals', 'module' => 'Marketing',
                'help' => 'Deal images shown on the coupons page (normal or discount).',
                'fields' => ['type' => ['t' => 'select', 'label' => 'Type', 'req' => 1, 'opts' => ['normal' => 'Normal', 'discount' => 'Discount']], 'image' => ['t' => 'image', 'label' => 'Image', 'folder' => 'coupons', 'req' => 1], 'status' => $status],
                'list' => ['image', 'type', 'status', 'created_at'], 'search' => ['type'], 'order' => ['id', 'desc'], 'stamp' => 'created_at'],
            'certs' => ['table' => 'certificates', 'title' => 'Certificate', 'plural' => 'Certificates', 'module' => 'Marketing',
                'help' => 'Licences and quality certificates shown on the website (FSSAI and others).',
                'fields' => ['title' => ['t' => 'text', 'label' => 'Title', 'req' => 1, 'max' => 150], 'image' => ['t' => 'image', 'label' => 'Image', 'folder' => 'certificates', 'req' => 1]],
                'list' => ['image', 'title'], 'search' => ['title'], 'order' => ['id', 'asc'], 'delete' => true],

            /* ---------------- content */
            'policies' => ['table' => 'policies', 'title' => 'Policy page', 'plural' => 'Policies & pages', 'module' => 'Content',
                'help' => 'Privacy, shipping, terms, about, refund and FAQ pages of the website. The text can contain simple HTML (headings, lists, links).',
                'fields' => ['type' => ['t' => 'select', 'label' => 'Page', 'req' => 1, 'unique' => 1, 'opts' => ['privacy-policy' => 'Privacy policy', 'shipping_policy' => 'Shipping policy', 'terms' => 'Terms & conditions', 'about' => 'About us', 'refund_ploicy' => 'Refund policy', 'faq' => 'FAQ']],
                    'content' => ['t' => 'html', 'label' => 'Page text', 'req' => 1, 'max' => 200000], 'status' => $status],
                'list' => ['type', 'content', 'status'], 'search' => ['content'], 'order' => ['id', 'asc']],
            'privacy' => ['table' => 'privacy_policy', 'title' => 'Old privacy text', 'plural' => 'Old privacy text', 'module' => 'Content',
                'fields' => ['title' => ['t' => 'text', 'label' => 'Title', 'req' => 1, 'max' => 200], 'description' => ['t' => 'html', 'label' => 'Text', 'max' => 200000]],
                'list' => ['title', 'description'], 'search' => ['title'], 'order' => ['id', 'asc'], 'add' => false],
            'grievcat' => ['table' => 'grievance_category', 'title' => 'Grievance category', 'plural' => 'Grievance categories', 'module' => 'Content',
                'help' => 'Reasons customer care can pick when logging a complaint.',
                'fields' => ['name' => ['t' => 'text', 'label' => 'Name', 'req' => 1, 'max' => 100, 'unique' => 1]], 'list' => ['name'], 'search' => ['name'], 'order' => ['id', 'asc'], 'delete' => true],

            /* ---------------- enquiries (read only) */
            'newsletter' => ['table' => 'news_letter', 'title' => 'Newsletter sign-up', 'plural' => 'Newsletter sign-ups', 'module' => 'Enquiries', 'readonly' => true,
                'fields' => ['email' => ['t' => 'text', 'label' => 'Email']], 'list' => ['email'], 'search' => ['email'], 'order' => ['id', 'desc']],
            'contact' => ['table' => 'contact_us', 'title' => 'Contact message', 'plural' => 'Contact us messages', 'module' => 'Enquiries', 'readonly' => true,
                'fields' => ['fname' => ['t' => 'text', 'label' => 'First name'], 'lname' => ['t' => 'text', 'label' => 'Last name'], 'email' => ['t' => 'text', 'label' => 'Email'], 'phone' => ['t' => 'text', 'label' => 'Phone'], 'query' => ['t' => 'textarea', 'label' => 'Message'], 'created_at' => ['t' => 'text', 'label' => 'Received']],
                'list' => ['created_at', 'fname', 'lname', 'email', 'phone', 'query'], 'search' => ['fname', 'lname', 'email', 'phone', 'query'], 'order' => ['id', 'desc']],
            'franchise' => ['table' => 'frenchisee_enquiry', 'title' => 'Franchise enquiry', 'plural' => 'Franchise enquiries', 'module' => 'Enquiries', 'readonly' => true,
                'fields' => ['name' => ['t' => 'text', 'label' => 'Name'], 'phone' => ['t' => 'text', 'label' => 'Phone'], 'email' => ['t' => 'text', 'label' => 'Email']],
                'list' => ['name', 'phone', 'email'], 'search' => ['name', 'phone', 'email'], 'order' => ['id', 'desc']],
        ];
    }

    public static function get(string $key): ?array
    {
        $a = self::all();
        return $a[$key] ?? null;
    }
}
