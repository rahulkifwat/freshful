<?php

return [
    App\Providers\AppServiceProvider::class,
    ...(class_exists(App\Providers\BuyerAppServiceProvider::class) ? [App\Providers\BuyerAppServiceProvider::class] : []),
    ...(class_exists(App\Providers\BuyerAppShopServiceProvider::class) ? [App\Providers\BuyerAppShopServiceProvider::class] : []),
];
