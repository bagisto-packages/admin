<?php

namespace BagistoPackages\Admin\Http\Controllers\Customer;

use BagistoPackages\Admin\Http\Controllers\Controller;
use BagistoPackages\Shop\Repositories\CustomerGroupRepository;

class CustomerGroupController extends Controller
{
    /**
     * CustomerGroupRepository object
     *
     * @var CustomerGroupRepository
     */
    protected $customerGroupRepository;

    /**
     * Create a new controller instance.
     *
     * @param CustomerGroupRepository $customerGroupRepository ;
     * @return void
     */
    public function __construct(CustomerGroupRepository $customerGroupRepository)
    {
        $this->middleware('admin');

        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::customers.groups.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        return view('admin::customers.groups.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store()
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:customer_groups,code', new \BagistoPackages\Core\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $data = request()->all();

        $data['is_user_defined'] = 1;

        $this->customerGroupRepository->create($data);

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Customer Group']));

        return redirect()->route('admin.groups.index');
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
        $group = $this->customerGroupRepository->findOrFail($id);

        return view('admin::customers.groups.edit', compact('group'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function update($id)
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:customer_groups,code,' . $id, new \BagistoPackages\Core\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $this->customerGroupRepository->update(request()->all(), $id);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Customer Group']));

        return redirect()->route('admin.groups.index');
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
        $customerGroup = $this->customerGroupRepository->findOrFail($id);

        if ($customerGroup->is_user_defined == 0) {
            session()->flash('warning', trans('admin::app.customers.customers.group-default'));
        } elseif (count($customerGroup->customer) > 0) {
            session()->flash('warning', trans('admin::app.response.customer-associate', ['name' => 'Customer Group']));
        } else {
            try {
                $this->customerGroupRepository->delete($id);

                session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Customer Group']));

                return response()->json(['message' => true], 200);
            } catch (\Exception $e) {
                session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Customer Group']));
            }
        }

        return response()->json(['message' => false], 400);
    }
}
