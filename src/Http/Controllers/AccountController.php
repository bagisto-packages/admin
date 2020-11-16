<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Illuminate\Support\Facades\Event;

class AccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('customer');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function edit()
    {
        $user = auth()->guard('admin')->user();

        return view('admin::account.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update()
    {
        $isPasswordChanged = false;
        $user = auth()->guard('admin')->user();

        $this->validate(request(), [
            'name' => 'required',
            'email' => 'email|unique:admins,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'current_password' => 'required|min:6',
        ]);

        $data = request()->input();

        if (!Hash::check($data['current_password'], auth()->guard('admin')->user()->password)) {
            session()->flash('warning', trans('admin::app.users.users.password-match'));

            return redirect()->back();
        }

        if (!$data['password']) {
            unset($data['password']);
        } else {
            $isPasswordChanged = true;
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        if ($isPasswordChanged) {
            Event::dispatch('user.admin.update-password', $user);
        }

        session()->flash('success', trans('admin::app.users.users.account-save'));

        return back();
    }
}
