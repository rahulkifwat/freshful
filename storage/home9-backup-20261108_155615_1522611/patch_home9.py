import sys, os, re

app = sys.argv[1]
def rd(p):
    return open(os.path.join(app, p), encoding='utf-8').read()
def wr(p, s):
    open(os.path.join(app, p), 'w', encoding='utf-8').write(s)
def once(s, old, new, label):
    n = s.count(old)
    if n != 1:
        print('ANCHOR PROBLEM (%s): found %d times, expected 1. Nothing was changed.' % (label, n))
        sys.exit(2)
    return s.replace(old, new)

HEX = ['hero_bg', 'hero_text', 'hero_btn_from', 'hero_btn_to', 'express_bg', 'express_text', 'express_icon', 'offer_from', 'offer_to', 'offer_text']
changed = {}

# 1. Home page view
p = 'resources/views/home_v2/index.blade.php'
s = rd(p)
if 'home_v2.extras' not in s:
    s = once(s, '<main class="ffh">', "@include('home_v2.extras')\n<main class=\"ffh\">", 'main tag')
    img = """<img src="{{ $categoryHd[(int) $category->id] ?? asset('uploads/images/'.$category->circle_image) }}" alt="{{ $category->name }}" width="160" height="160" loading="lazy" decoding="async" onerror="this.style.visibility='hidden'">"""
    s = once(s, img, '<span class="ffh-cat-pic">' + img + '</span>', 'category picture')
    s = once(s, '<h3>Express in 30 minutes</h3>', "<h3>{{ app(\\App\\Services\\Storefront\\StorefrontSettings::class)->get('express_title', 'Express in 30 minutes') }}</h3>", 'express title')
    s = once(s, '<p>From your nearest store, 7 AM to 9 PM. Or choose a time slot that suits you.</p>', "<p>{{ app(\\App\\Services\\Storefront\\StorefrontSettings::class)->get('express_sub', 'From your nearest store, 7 AM to 9 PM. Or choose a time slot that suits you.') }}</p>", 'express text')
    s = once(s, "@if ($sectionIndex === 1 && $ui['coupons'])", "@include('home_v2.ribbons', ['ffAfter' => $section['name'], 'ffIndex' => $sectionIndex])\n            @if ($sectionIndex === 1 && $ui['coupons'])", 'ribbon include')
    changed[p] = s

# 2. Settings class
p = 'app/Services/Storefront/StorefrontSettings.php'
s = rd(p)
if 'hero_anim' not in s:
    new_defaults = """        'low_stock_at' => '5',
        'hero_anim' => 'slide',
        'hero_auto' => '1',
        'hero_bg' => '#FFF1EA',
        'hero_text' => '#1C1C1E',
        'hero_btn_from' => '#F70202',
        'hero_btn_to' => '#FF5500',
        'express_title' => 'Express in 30 minutes',
        'express_sub' => 'From your nearest store, 7 AM to 9 PM. Or choose a time slot that suits you.',
        'express_bg' => '#FFFFFF',
        'express_text' => '#1C1C1E',
        'express_icon' => '#F70202',
        'offer_from' => '#F70202',
        'offer_to' => '#FF5500',
        'offer_text' => '#FFFFFF',
        'coupon_scroll' => '1',
        'coupon_seconds' => '3',
        'show_ribbons' => '1',
    ];"""
    s = once(s, "        'low_stock_at' => '5',\n    ];", new_defaults, 'defaults')
    s = once(s, "public const FLAGS = ['show_side', 'show_trust', 'show_categories', 'show_buy_again', 'show_coupons', 'stock_badges'];",
             "public const FLAGS = ['show_side', 'show_trust', 'show_categories', 'show_buy_again', 'show_coupons', 'stock_badges', 'hero_auto', 'coupon_scroll', 'show_ribbons'];", 'flags')
    changed[p] = s

# 3. Controller: validate and save the new fields
p = 'app/Http/Controllers/Storefront/HomeSettingsController.php'
s = rd(p)
if 'hero_anim' not in s:
    rules = "            'hidden.*' => ['string', 'max:80'],\n"
    rules += "            'hero_anim' => ['required', 'in:slide,fade,zoom'],\n"
    rules += "            'coupon_seconds' => ['required', 'integer', 'min:2', 'max:15'],\n"
    rules += "            'express_title' => ['required', 'string', 'max:60'],\n"
    rules += "            'express_sub' => ['nullable', 'string', 'max:160'],\n"
    for k in HEX:
        rules += "            '%s' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],\n" % k
    rules += "        ]);"
    s = once(s, "            'hidden.*' => ['string', 'max:80'],\n        ]);", rules, 'validation')
    vals = """            'hidden_tags' => implode('|', array_map('trim', $data['hidden'] ?? [])),
            'hero_anim' => $data['hero_anim'],
            'coupon_seconds' => (int) $data['coupon_seconds'],
            'express_title' => trim($data['express_title']),
            'express_sub' => trim((string) ($data['express_sub'] ?? '')),
        ];
        foreach (%s as $ffHex) {
            if (! empty($data[$ffHex])) {
                $values[$ffHex] = strtoupper($data[$ffHex]);
            }
        }""" % ('[' + ', '.join("'%s'" % k for k in HEX) + ']')
    s = once(s, "            'hidden_tags' => implode('|', array_map('trim', $data['hidden'] ?? [])),\n        ];", vals, 'values')
    changed[p] = s

# 4. Admin page: new sections in the controls form
p = 'resources/views/home_v2/admin_settings.blade.php'
s = rd(p)
if 'hero_anim' not in s:
    def col(name, label):
        return '<div><label for="c_%s">%s</label><input type="color" id="c_%s" name="%s" value="{{ $settings[\'%s\'] }}"></div>' % (name, label, name, name, name)
    block = '''            <h3>5. Main banner: animation and colours</h3>
            <p class="sub">Banner pictures are added in Admin &rarr; Marketing &rarr; Website banners. Best picture size: 1880 x 660 pixels (wide). Pictures are never stretched; the extra edges are trimmed to fit.</p>
            <div class="row">
                <div><label for="ha">How slides change</label>
                    <select id="ha" name="hero_anim">
                        <option value="slide" @selected($settings['hero_anim'] === 'slide')>Slide sideways</option>
                        <option value="fade" @selected($settings['hero_anim'] === 'fade')>Fade</option>
                        <option value="zoom" @selected($settings['hero_anim'] === 'zoom')>Fade with a slow zoom</option>
                    </select></div>
                <div class="checks" style="margin:0"><label><input type="checkbox" name="hero_auto" value="1" @checked($settings['hero_auto'] !== '0')> Change slides by themselves</label></div>
                ''' + col('hero_bg', 'Product slide background') + '''
                ''' + col('hero_text', 'Product name colour') + '''
                ''' + col('hero_btn_from', 'Button colour 1') + '''
                ''' + col('hero_btn_to', 'Button colour 2') + '''
            </div>

            <h3>6. Express and offer tiles (next to the banner)</h3>
            <div class="row">
                <div><label for="et">Express heading</label><input type="text" id="et" name="express_title" maxlength="60" value="{{ $settings['express_title'] }}" style="min-width:260px"></div>
                <div><label for="es">Express line</label><input type="text" id="es" name="express_sub" maxlength="160" value="{{ $settings['express_sub'] }}" style="min-width:420px"></div>
            </div>
            <div class="row" style="margin-top:12px">
                ''' + col('express_bg', 'Express tile background') + '''
                ''' + col('express_text', 'Express text colour') + '''
                ''' + col('express_icon', 'Express icon colour') + '''
                ''' + col('offer_from', 'Offer tile colour 1') + '''
                ''' + col('offer_to', 'Offer tile colour 2') + '''
                ''' + col('offer_text', 'Offer text colour') + '''
            </div>

            <h3>7. Coupons for you</h3>
            <div class="row">
                <div class="checks" style="margin:0"><label><input type="checkbox" name="coupon_scroll" value="1" @checked($settings['coupon_scroll'] !== '0')> Move the coupon row by itself</label></div>
                <div><label for="cs2">Move every (seconds)</label><input type="number" id="cs2" name="coupon_seconds" min="2" max="15" value="{{ $settings['coupon_seconds'] }}"></div>
            </div>

            <h3>8. Ribbon banners and picture tiles</h3>
            <p class="sub">Add them once in Admin &rarr; Marketing &rarr; App control. They then show in the app and here on the website, in the same place. If no place is set, the ribbon sits in the middle of the page.</p>
            <div class="checks"><label><input type="checkbox" name="show_ribbons" value="1" @checked($settings['show_ribbons'] !== '0')> Show ribbon banners and picture tiles on the website</label></div>
'''
    anchor = '            <p style="margin-top:16px"><button class="btn" type="submit">Save controls</button></p>'
    s = once(s, anchor, block + anchor, 'admin form')
    changed[p] = s

if not changed:
    print('Already applied. Nothing to do.')
    sys.exit(0)
for p, s in changed.items():
    wr(p, s)
    print('patched:', p)
