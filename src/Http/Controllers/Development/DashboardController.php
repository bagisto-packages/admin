<?php

namespace BagistoPackages\Admin\Http\Controllers\Development;

use BagistoPackages\Admin\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::settings.development.dashboard');
    }
}
