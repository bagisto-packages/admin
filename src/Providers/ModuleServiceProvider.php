<?php

namespace BagistoPackages\Admin\Providers;

use BagistoPackages\Shop\Tree;
use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Models\Admin;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function boot()
    {
        parent::boot();

        config()->set('auth.providers', array_merge(config('auth.providers'), [
            'admins' => [
                'driver' => 'eloquent',
                'model' => Admin::class
            ]
        ]));

        config()->set('auth.guards', array_merge(config('auth.guards'), [
            'admin' => [
                'driver' => 'session',
                'provider' => 'admins'
            ]
        ]));

        config()->set('auth.passwords', array_merge(config('auth.passwords'), [
            'admins' => [
                'provider' => 'admins',
                'table' => 'admin_password_resets',
                'expire' => 60,
            ]
        ]));

        $this->composeView();

        $this->registerACL();

        Event::listen('user.admin.update-password', 'BagistoPackages\Admin\Listeners\PasswordChange@sendUpdatePasswordMail');
        Event::listen('checkout.order.save.after', 'BagistoPackages\Admin\Listeners\Order@sendNewOrderMail');
        Event::listen('sales.invoice.save.after', 'BagistoPackages\Admin\Listeners\Order@sendNewInvoiceMail');
        Event::listen('sales.shipment.save.after', 'BagistoPackages\Admin\Listeners\Order@sendNewShipmentMail');
        Event::listen('sales.order.cancel.after', 'BagistoPackages\Admin\Listeners\Order@sendCancelOrderMail');
        Event::listen('sales.refund.save.after', 'BagistoPackages\Admin\Listeners\Order@sendNewRefundMail');
        Event::listen('sales.order.comment.create.after', 'BagistoPackages\Admin\Listeners\Order@sendOrderCommentMail');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'admin');

        $this->publishes([
            __DIR__ . '/../Resources/lang' => resource_path('lang/vendor/admin'),
        ]);

        $this->publishes([
            __DIR__ . '/../../publishable/assets' => public_path('vendor/packages/admin/assets'),
        ], 'public');
    }

    public function register()
    {
        parent::register();

        $this->registerConfig();
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Resources/config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Resources/config/acl.php', 'acl');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Resources/config/system.php', 'core');
    }

    protected function composeView()
    {
        view()->composer(['admin::layouts.nav-left', 'admin::layouts.nav-aside', 'admin::layouts.tabs'], function ($view) {
            $tree = Tree::create();
            $permissionType = auth()->guard('admin')->user()->role->permission_type;
            $allowedPermissions = auth()->guard('admin')->user()->role->permissions;

            foreach (config('menu.admin') as $index => $item) {
                if (!bouncer()->hasPermission($item['key'])) {
                    continue;
                }

                if ($index + 1 < count(config('menu.admin')) && $permissionType != 'all') {
                    $permission = config('menu.admin')[$index + 1];

                    if (substr_count($permission['key'], '.') == 2 && substr_count($item['key'], '.') == 1) {
                        foreach ($allowedPermissions as $key => $value) {
                            if ($item['key'] == $value) {
                                $neededItem = $allowedPermissions[$key + 1];

                                foreach (config('menu.admin') as $key1 => $findMatced) {
                                    if ($findMatced['key'] == $neededItem) {
                                        $item['route'] = $findMatced['route'];
                                    }
                                }
                            }
                        }
                    }
                }

                $tree->add($item, 'menu');
            }

            $tree->items = core()->sortItems($tree->items);

            $view->with('menu', $tree);
        });

        view()->composer(['admin::users.roles.create', 'admin::users.roles.edit'], function ($view) {
            $view->with('acl', $this->createACL());
        });

        view()->composer(['admin::catalog.products.create'], function ($view) {
            $items = [];

            foreach (config('product_types') as $item) {
                $item['children'] = [];
                array_push($items, $item);
            }

            $types = core()->sortItems($items);

            $view->with('productTypes', $types);
        });
    }

    public function registerACL()
    {
        $this->app->singleton('acl', function () {
            return $this->createACL();
        });
    }

    public function createACL()
    {
        static $tree;

        if ($tree) {
            return $tree;
        }

        $tree = Tree::create();

        foreach (config('acl') as $item) {
            $tree->add($item, 'acl');
        }

        $tree->items = core()->sortItems($tree->items);

        return $tree;
    }
}
