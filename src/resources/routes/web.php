<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {
    Route::get('/', 'Controller@redirectToLogin');
    Route::get('/login', 'SessionController@create')->name('session.create');
    Route::post('/login', 'SessionController@store')->name('session.store');
    Route::get('/forget-password', 'ForgetPasswordController@create')->name('forget-password.create');
    Route::post('/forget-password', 'ForgetPasswordController@store')->name('forget-password.store');
    Route::get('/reset-password/{token}', 'ResetPasswordController@create')->name('reset-password.create');
    Route::post('/reset-password', 'ResetPasswordController@store')->name('reset-password.store');

    Route::group(['middleware' => ['admin']], function () {
        Route::get('/logout', 'SessionController@destroy')->name('session.destroy');
        Route::get('dashboard', 'DashboardController@index')->name('dashboard.index');
        Route::get('customers', 'Customer\CustomerController@index')->name('customer.index');
        Route::get('customers/create', 'Customer\CustomerController@create')->name('customer.create');
        Route::post('customers/create', 'Customer\CustomerController@store')->name('customer.store');
        Route::get('customers/edit/{id}', 'Customer\CustomerController@edit')->name('customer.edit');
        Route::get('customers/note/{id}', 'Customer\CustomerController@createNote')->name('customer.note.create');
        Route::put('customers/note/{id}', 'Customer\CustomerController@storeNote')->name('customer.note.store');
        Route::put('customers/edit/{id}', 'Customer\CustomerController@update')->name('customer.update');
        Route::post('customers/delete/{id}', 'Customer\CustomerController@destroy')->name('customer.delete');
        Route::post('customers/masssdelete', 'Customer\CustomerController@massDestroy')->name('customer.mass-delete');
        Route::post('customers/masssupdate', 'Customer\CustomerController@massUpdate')->name('customer.mass-update');

        Route::get('reviews', 'ReviewController@index')->name('customer.review.index');

        Route::get('customers/{id}/addresses', 'Customer\AddressController@index')->name('customer.addresses.index');
        Route::get('customers/{id}/addresses/create', 'Customer\AddressController@create')->name('customer.addresses.create');
        Route::post('customers/{id}/addresses/create', 'Customer\AddressController@store')->name('customer.addresses.store');
        Route::get('customers/addresses/edit/{id}', 'Customer\AddressController@edit')->name('customer.addresses.edit');
        Route::put('customers/addresses/edit/{id}', 'Customer\AddressController@update')->name('customer.addresses.update');
        Route::post('customers/addresses/delete/{id}', 'Customer\AddressController@destroy')->name('customer.addresses.delete');
        Route::post('customers/{id}/addresses', 'Customer\AddressController@massDestroy')->name('customer.addresses.massdelete');

        Route::get('configuration/{slug?}/{slug2?}', 'ConfigurationController@index')->name('configuration.index');
        Route::post('configuration/{slug?}/{slug2?}', 'ConfigurationController@store')->name('configuration.index.store');
        Route::get('configuration/{slug?}/{slug2?}/{path}', 'ConfigurationController@download')->name('configuration.download');

        Route::get('reviews/edit/{id}', 'ReviewController@edit')->name('customer.review.edit');
        Route::put('reviews/edit/{id}', 'ReviewController@update')->name('customer.review.update');
        Route::post('reviews/delete/{id}', 'ReviewController@destroy')->name('customer.review.delete');
        Route::post('reviews/massdestroy', 'ReviewController@massDestroy')->name('customer.review.massdelete');
        Route::post('reviews/massupdate', 'ReviewController@massUpdate')->name('customer.review.massupdate');

        Route::get('groups', 'Customer\CustomerGroupController@index')->name('groups.index');
        Route::get('groups/create', 'Customer\CustomerGroupController@create')->name('groups.create');
        Route::post('groups/create', 'Customer\CustomerGroupController@store')->name('groups.store');
        Route::get('groups/edit/{id}', 'Customer\CustomerGroupController@edit')->name('groups.edit');
        Route::put('groups/edit/{id}', 'Customer\CustomerGroupController@update')->name('groups.update');
        Route::post('groups/delete/{id}', 'Customer\CustomerGroupController@destroy')->name('groups.delete');

        Route::prefix('sales')->group(function () {
            Route::get('/orders', 'Sales\OrderController@index')->name('sales.orders.index');
            Route::get('/orders/view/{id}', 'Sales\OrderController@view')->name('sales.orders.view');
            Route::get('/orders/cancel/{id}', 'Sales\OrderController@cancel')->name('sales.orders.cancel');
            Route::post('/orders/create/{order_id}', 'Sales\OrderController@comment')->name('sales.orders.comment');

            Route::get('/invoices', 'Sales\InvoiceController@index')->name('sales.invoices.index');
            Route::get('/invoices/create/{order_id}', 'Sales\InvoiceController@create')->name('sales.invoices.create');
            Route::post('/invoices/create/{order_id}', 'Sales\InvoiceController@store')->name('sales.invoices.store');
            Route::get('/invoices/view/{id}', 'Sales\InvoiceController@view')->name('sales.invoices.view');
            Route::get('/invoices/print/{id}', 'Sales\InvoiceController@print')->name('sales.invoices.print');

            Route::get('/shipments', 'Sales\ShipmentController@index')->name('sales.shipments.index');
            Route::get('/shipments/create/{order_id}', 'Sales\ShipmentController@create')->name('sales.shipments.create');
            Route::post('/shipments/create/{order_id}', 'Sales\ShipmentController@store')->name('sales.shipments.store');
            Route::get('/shipments/view/{id}', 'Sales\ShipmentController@view')->name('sales.shipments.view');

            Route::get('/refunds', 'Sales\RefundController@index')->name('sales.refunds.index');
            Route::get('/refunds/create/{order_id}', 'Sales\RefundController@create')->name('sales.refunds.create');
            Route::post('/refunds/create/{order_id}', 'Sales\RefundController@store')->name('sales.refunds.store');
            Route::post('/refunds/update-qty/{order_id}', 'Sales\RefundController@updateQty')->name('sales.refunds.update_qty');
            Route::get('/refunds/view/{id}', 'Sales\RefundController@view')->name('sales.refunds.view');
        });

        Route::prefix('catalog')->group(function () {
            Route::get('/sync', 'ProductController@sync');

            Route::get('/products', 'ProductController@index')->name('catalog.products.index');
            Route::get('/products/create', 'ProductController@create')->name('catalog.products.create');
            Route::post('/products/create', 'ProductController@store')->name('catalog.products.store');
            Route::get('products/copy/{id}', 'ProductController@copy')->name('catalog.products.copy');
            Route::get('/products/edit/{id}', 'ProductController@edit')->name('catalog.products.edit');
            Route::put('/products/edit/{id}', 'ProductController@update')->name('catalog.products.update');
            Route::post('/products/upload-file/{id}', 'ProductController@uploadLink')->name('catalog.products.upload_link');
            Route::post('/products/upload-sample/{id}', 'ProductController@uploadSample')->name('catalog.products.upload_sample');
            Route::post('/products/delete/{id}', 'ProductController@destroy')->name('catalog.products.delete');
            Route::post('products/massaction', 'ProductController@massActionHandler')->name('catalog.products.massaction');
            Route::post('products/massdelete', 'ProductController@massDestroy')->name('catalog.products.massdelete');
            Route::post('products/massupdate', 'ProductController@massUpdate')->name('catalog.products.massupdate');
            Route::get('products/search', 'ProductController@productLinkSearch')->name('catalog.products.productlinksearch');
            Route::get('products/search-simple-products', 'ProductController@searchSimpleProducts')->name('catalog.products.search_simple_product');
            Route::get('/products/{id}/{attribute_id}', 'ProductController@download')->name('catalog.products.file.download');

            Route::get('/categories', 'CategoryController@index')->name('catalog.categories.index');
            Route::get('/categories/create', 'CategoryController@create')->name('catalog.categories.create');
            Route::post('/categories/create', 'CategoryController@store')->name('catalog.categories.store');
            Route::get('/categories/edit/{id}', 'CategoryController@edit')->name('catalog.categories.edit');
            Route::put('/categories/edit/{id}', 'CategoryController@update')->name('catalog.categories.update');
            Route::post('/categories/delete/{id}', 'CategoryController@destroy')->name('catalog.categories.delete');
            Route::post('categories/massdelete', 'CategoryController@massDestroy')->name('catalog.categories.massdelete');
            Route::post('/categories/product/count', 'CategoryController@categoryProductCount')->name('catalog.categories.product.count');

            Route::get('/attributes', 'AttributeController@index')->name('catalog.attributes.index');
            Route::get('/attributes/create', 'AttributeController@create')->name('catalog.attributes.create');
            Route::post('/attributes/create', 'AttributeController@store')->name('catalog.attributes.store');
            Route::get('/attributes/edit/{id}', 'AttributeController@edit')->name('catalog.attributes.edit');
            Route::put('/attributes/edit/{id}', 'AttributeController@update')->name('catalog.attributes.update');
            Route::post('/attributes/delete/{id}', 'AttributeController@destroy')->name('catalog.attributes.delete');
            Route::post('/attributes/massdelete', 'AttributeController@massDestroy')->name('catalog.attributes.massdelete');

            Route::get('/families', 'AttributeFamilyController@index')->name('catalog.families.index');
            Route::get('/families/create', 'AttributeFamilyController@create')->name('catalog.families.create');
            Route::post('/families/create', 'AttributeFamilyController@store')->name('catalog.families.store');
            Route::get('/families/edit/{id}', 'AttributeFamilyController@edit')->name('catalog.families.edit');
            Route::put('/families/edit/{id}', 'AttributeFamilyController@update')->name('catalog.families.update');
            Route::post('/families/delete/{id}', 'AttributeFamilyController@destroy')->name('catalog.families.delete');
        });

        Route::get('/users', 'UserController@index')->name('users.index');
        Route::get('/users/create', 'UserController@create')->name('users.create');
        Route::post('/users/create', 'UserController@store')->name('users.store');
        Route::get('/users/edit/{id}', 'UserController@edit')->name('users.edit');
        Route::put('/users/edit/{id}', 'UserController@update')->name('users.update');
        Route::post('/users/delete/{id}', 'UserController@destroy')->name('users.delete');
        Route::get('/users/confirm/{id}', 'UserController@confirm')->name('super.users.confirm');
        Route::post('/users/confirm/{id}', 'UserController@destroySelf')->name('users.destroy');

        Route::get('/roles', 'RoleController@index')->name('roles.index');
        Route::get('/roles/create', 'RoleController@create')->name('roles.create');
        Route::post('/roles/create', 'RoleController@store')->name('roles.store');
        Route::get('/roles/edit/{id}', 'RoleController@edit')->name('roles.edit');
        Route::put('/roles/edit/{id}', 'RoleController@update')->name('roles.update');
        Route::post('/roles/delete/{id}', 'RoleController@destroy')->name('roles.delete');

        Route::get('/locales', 'LocaleController@index')->name('locales.index');
        Route::get('/locales/create', 'LocaleController@create')->name('locales.create');
        Route::post('/locales/create', 'LocaleController@store')->name('locales.store');
        Route::get('/locales/edit/{id}', 'LocaleController@edit')->name('locales.edit');
        Route::put('/locales/edit/{id}', 'LocaleController@update')->name('locales.update');
        Route::post('/locales/delete/{id}', 'LocaleController@destroy')->name('locales.delete');

        Route::get('/currencies', 'CurrencyController@index')->name('currencies.index');
        Route::get('/currencies/create', 'CurrencyController@create')->name('currencies.create');
        Route::post('/currencies/create', 'CurrencyController@store')->name('currencies.store');
        Route::get('/currencies/edit/{id}', 'CurrencyController@edit')->name('currencies.edit');
        Route::put('/currencies/edit/{id}', 'CurrencyController@update')->name('currencies.update');
        Route::post('/currencies/delete/{id}', 'CurrencyController@destroy')->name('currencies.delete');
        Route::post('/currencies/massdelete', 'CurrencyController@massDestroy')->name('currencies.massdelete');

        Route::get('/exchange_rates', 'ExchangeRateController@index')->name('exchange_rates.index');

        Route::get('/exchange_rates/create', 'ExchangeRateController@create')->name('exchange_rates.create');
        Route::post('/exchange_rates/create', 'ExchangeRateController@store')->name('exchange_rates.store');
        Route::get('/exchange_rates/edit/{id}', 'ExchangeRateController@edit')->name('exchange_rates.edit');
        Route::get('/exchange_rates/update-rates', 'ExchangeRateController@updateRates')->name('exchange_rates.update_rates');
        Route::put('/exchange_rates/edit/{id}', 'ExchangeRateController@update')->name('exchange_rates.update');
        Route::post('/exchange_rates/delete/{id}', 'ExchangeRateController@destroy')->name('exchange_rates.delete');

        Route::get('/inventory_sources', 'InventorySourceController@index')->name('inventory_sources.index');
        Route::get('/inventory_sources/create', 'InventorySourceController@create')->name('inventory_sources.create');
        Route::post('/inventory_sources/create', 'InventorySourceController@store')->name('inventory_sources.store');
        Route::get('/inventory_sources/edit/{id}', 'InventorySourceController@edit')->name('inventory_sources.edit');
        Route::put('/inventory_sources/edit/{id}', 'InventorySourceController@update')->name('inventory_sources.update');
        Route::post('/inventory_sources/delete/{id}', 'InventorySourceController@destroy')->name('inventory_sources.delete');

        Route::get('/channels', 'ChannelController@index')->name('channels.index');
        Route::get('/channels/create', 'ChannelController@create')->name('channels.create');
        Route::post('/channels/create', 'ChannelController@store')->name('channels.store');
        Route::get('/channels/edit/{id}', 'ChannelController@edit')->name('channels.edit');
        Route::put('/channels/edit/{id}', 'ChannelController@update')->name('channels.update');
        Route::post('/channels/delete/{id}', 'ChannelController@destroy')->name('channels.delete');

        Route::get('/account', 'AccountController@edit')->name('account.edit');
        Route::put('/account', 'AccountController@update')->name('account.update');

        Route::get('/subscribers', 'SubscriptionController@index')->name('customers.subscribers.index');
        Route::post('subscribers/delete/{id}', 'SubscriptionController@destroy')->name('customers.subscribers.delete');
        Route::get('subscribers/edit/{id}', 'SubscriptionController@edit')->name('customers.subscribers.edit');
        Route::put('subscribers/update/{id}', 'SubscriptionController@update')->name('customers.subscribers.update');

        Route::get('/tax-categories', 'TaxController@index')->name('tax-categories.index');
        Route::get('/tax-categories/create', 'TaxCategoryController@show')->name('tax-categories.show');
        Route::post('/tax-categories/create', 'TaxCategoryController@create')->name('tax-categories.create');
        Route::get('/tax-categories/edit/{id}', 'TaxCategoryController@edit')->name('tax-categories.edit');
        Route::put('/tax-categories/edit/{id}', 'TaxCategoryController@update')->name('tax-categories.update');
        Route::post('/tax-categories/delete/{id}', 'TaxCategoryController@destroy')->name('tax-categories.delete');

        Route::get('tax-rates', 'TaxRateController@index')->name('tax-rates.index');
        Route::get('tax-rates/create', 'TaxRateController@show')->name('tax-rates.show');
        Route::post('tax-rates/create', 'TaxRateController@create')->name('tax-rates.create');
        Route::get('tax-rates/edit/{id}', 'TaxRateController@edit')->name('tax-rates.store');
        Route::put('tax-rates/update/{id}', 'TaxRateController@update')->name('tax-rates.update');
        Route::post('/tax-rates/delete/{id}', 'TaxRateController@destroy')->name('tax-rates.delete');
        Route::post('/tax-rates/import', 'TaxRateController@import')->name('tax-rates.import');

        Route::post('/export', 'ExportController@export')->name('datagrid.export');

        Route::prefix('promotions')->group(function () {
            Route::get('cart-rules', 'CartRuleController@index')->name('cart-rules.index');
            Route::get('cart-rules/create', 'CartRuleController@create')->name('cart-rules.create');
            Route::post('cart-rules/create', 'CartRuleController@store')->name('cart-rules.store');
            Route::get('cart-rules/copy/{id}', 'CartRuleController@copy')->name('cart-rules.copy');
            Route::get('cart-rules/edit/{id}', 'CartRuleController@edit')->name('cart-rules.edit');
            Route::post('cart-rules/edit/{id}', 'CartRuleController@update')->name('cart-rules.update');
            Route::post('cart-rules/delete/{id}', 'CartRuleController@destroy')->name('cart-rules.delete');
            Route::post('cart-rules/generate-coupons/{id?}', 'CartRuleController@generateCoupons')->name('cart-rules.generate-coupons');
            Route::post('/massdelete', 'CartRuleCouponController@massDelete')->name('cart-rule-coupons.mass-delete');

            Route::get('catalog-rules', 'CatalogRuleController@index')->name('catalog-rules.index');
            Route::get('catalog-rules/create', 'CatalogRuleController@create')->name('catalog-rules.create');
            Route::post('catalog-rules/create', 'CatalogRuleController@store')->name('catalog-rules.store');
            Route::get('catalog-rules/edit/{id}', 'CatalogRuleController@edit')->name('catalog-rules.edit');
            Route::post('catalog-rules/edit/{id}', 'CatalogRuleController@update')->name('catalog-rules.update');
            Route::post('catalog-rules/delete/{id}', 'CatalogRuleController@destroy')->name('catalog-rules.delete');
        });

        Route::prefix('cms')->group(function () {
            Route::get('/', 'PageController@index')->name('cms.index');
            Route::get('create', 'PageController@create')->name('cms.create');
            Route::post('create', 'PageController@store')->name('cms.store');
            Route::get('edit/{id}', 'PageController@edit')->name('cms.edit');
            Route::post('edit/{id}', 'PageController@update')->name('cms.update');
            Route::post('/delete/{id}', 'PageController@delete')->name('cms.delete');
            Route::post('/massdelete', 'PageController@massDelete')->name('cms.mass-delete');
        });
    });
});