<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Hash;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use BagistoPackages\Admin\Http\Requests\UserForm;
use BagistoPackages\Admin\Repositories\RoleRepository;
use BagistoPackages\Admin\Repositories\AdminRepository;

class UserController extends Controller
{
    /**
     * AdminRepository object
     *
     * @var AdminRepository
     */
    protected $adminRepository;

    /**
     * RoleRepository object
     *
     * @var RoleRepository
     */
    protected $roleRepository;

    /**
     * Create a new controller instance.
     *
     * @param AdminRepository $adminRepository
     * @param RoleRepository $roleRepository
     * @return void
     */
    public function __construct(
        AdminRepository $adminRepository,
        RoleRepository $roleRepository
    )
    {
        $this->adminRepository = $adminRepository;
        $this->roleRepository = $roleRepository;

        $this->middleware('guest', ['except' => 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::users.users.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        $roles = $this->roleRepository->all();

        return view('admin::users.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserForm $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function store(UserForm $request)
    {
        $data = $request->all();

        if (isset($data['password']) && $data['password']) {
            $data['password'] = bcrypt($data['password']);
            $data['api_token'] = Str::random(80);
        }

        Event::dispatch('user.admin.create.before');

        $admin = $this->adminRepository->create($data);

        Event::dispatch('user.admin.create.after', $admin);

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'User']));

        return redirect()->route('admin.users.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $user = $this->adminRepository->findOrFail($id);

        $roles = $this->roleRepository->all();

        return view('admin::users.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserForm $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update(UserForm $request, $id)
    {
        $isPasswordChanged = false;

        $data = $request->all();

        if (!$data['password']) {
            unset($data['password']);
        } else {
            $isPasswordChanged = true;
            $data['password'] = bcrypt($data['password']);
        }

        if (isset($data['status'])) {
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
        }

        Event::dispatch('user.admin.update.before', $id);

        $admin = $this->adminRepository->update($data, $id);

        if ($isPasswordChanged) {
            Event::dispatch('user.admin.update-password', $admin);
        }

        Event::dispatch('user.admin.update.after', $admin);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'User']));

        return redirect()->route('admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function destroy($id)
    {
        $user = $this->adminRepository->findOrFail($id);

        if ($this->adminRepository->count() == 1) {
            session()->flash('error', trans('admin::app.response.last-delete-error', ['name' => 'Admin']));
        } else {
            Event::dispatch('user.admin.delete.before', $id);

            if (auth()->guard('admin')->user()->id == $id) {
                return response()->json([
                    'redirect' => route('super.users.confirm', ['id' => $id]),
                ]);
            }

            try {
                $this->adminRepository->delete($id);

                session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Admin']));

                Event::dispatch('user.admin.delete.after', $id);

                return response()->json(['message' => true], 200);
            } catch (Exception $e) {
                session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Admin']));
            }
        }

        return response()->json(['message' => false], 400);
    }

    /**
     * Show the form for confirming the user password.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function confirm($id)
    {
        $user = $this->adminRepository->findOrFail($id);

        return view('admin::customers.confirm-password', compact('user'));
    }

    /**
     * destroy current after confirming
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function destroySelf()
    {
        $password = request()->input('password');

        if (Hash::check($password, auth()->guard('admin')->user()->password)) {
            if ($this->adminRepository->count() == 1) {
                session()->flash('error', trans('admin::app.users.users.delete-last'));
            } else {
                $id = auth()->guard('admin')->user()->id;

                Event::dispatch('user.admin.delete.before', $id);

                $this->adminRepository->delete($id);

                Event::dispatch('user.admin.delete.after', $id);

                session()->flash('success', trans('admin::app.users.users.delete-success'));

                return redirect()->route('admin.session.create');
            }
        } else {
            session()->flash('warning', trans('admin::app.users.users.incorrect-password'));

            return redirect()->route('admin.users.index');
        }
    }
}
