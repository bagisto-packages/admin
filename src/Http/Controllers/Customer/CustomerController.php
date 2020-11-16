<?php

namespace BagistoPackages\Admin\Http\Controllers\Customer;

use Mail;
use Illuminate\Support\Facades\Event;
use BagistoPackages\Admin\Mail\NewCustomerNotification;
use BagistoPackages\Admin\Http\Controllers\Controller;
use BagistoPackages\Shop\Repositories\ChannelRepository;
use BagistoPackages\Shop\Repositories\CustomerRepository;
use BagistoPackages\Shop\Repositories\CustomerAddressRepository;
use BagistoPackages\Shop\Repositories\CustomerGroupRepository;

class CustomerController extends Controller
{
    /**
     * CustomerRepository object
     *
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * CustomerAddress Repository object
     *
     * @var CustomerAddressRepository
     */
    protected $customerAddressRepository;

    /**
     * CustomerGroupRepository object
     *
     * @var CustomerGroupRepository
     */
    protected $customerGroupRepository;

    /**
     * ChannelRepository object
     *
     * @var ChannelRepository
     */
    protected $channelRepository;

    /**
     * Create a new controller instance.
     *
     * @param CustomerRepository $customerRepository
     * @param CustomerAddressRepository $customerAddressRepository
     * @param CustomerGroupRepository $customerGroupRepository
     * @param ChannelRepository $channelRepository
     */
    public function __construct(
        CustomerRepository $customerRepository,
        CustomerAddressRepository $customerAddressRepository,
        CustomerGroupRepository $customerGroupRepository,
        ChannelRepository $channelRepository
    )
    {
        $this->middleware('admin');

        $this->customerRepository = $customerRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->channelRepository = $channelRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::customers.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        $customerGroup = $this->customerGroupRepository->findWhere([['code', '<>', 'guest']]);

        $channelName = $this->channelRepository->all();

        return view('admin::customers.create', compact('customerGroup', 'channelName'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function store()
    {
        $this->validate(request(), [
            'first_name' => 'string|required',
            'last_name' => 'string|required',
            'gender' => 'required',
            'email' => 'required|unique:customers,email',
            'date_of_birth' => 'date|before:today',
        ]);

        $data = request()->all();

        $password = rand(100000, 10000000);

        $data['password'] = bcrypt($password);

        $data['is_verified'] = 1;

        Event::dispatch('customer.registration.before');

        $customer = $this->customerRepository->create($data);

        Event::dispatch('customer.registration.after', $customer);

        try {
            $configKey = 'emails.general.notifications.emails.general.notifications.customer';
            if (core()->getConfigData($configKey)) {
                Mail::queue(new NewCustomerNotification($customer, $password));
            }
        } catch (\Exception $e) {
            report($e);
        }

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Customer']));

        return redirect()->route('admin.customer.index');
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
        $customer = $this->customerRepository->findOrFail($id);
        $address = $this->customerAddressRepository->find($id);
        $customerGroup = $this->customerGroupRepository->findWhere([['code', '<>', 'guest']]);
        $channelName = $this->channelRepository->all();

        return view('admin::customers.edit', compact('customer', 'address', 'customerGroup', 'channelName'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update($id)
    {
        $this->validate(request(), [
            'first_name' => 'string|required',
            'last_name' => 'string|required',
            'gender' => 'required',
            'email' => 'required|unique:customers,email,' . $id,
            'date_of_birth' => 'date|before:today',
        ]);

        $data = request()->all();

        $data['status'] = !isset($data['status']) ? 0 : 1;

        Event::dispatch('customer.update.before');

        $customer = $this->customerRepository->update($data, $id);

        Event::dispatch('customer.update.after', $customer);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Customer']));

        return redirect()->route('admin.customer.index');
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
        $customer = $this->customerRepository->findorFail($id);

        try {

            if (!$this->customerRepository->checkIfCustomerHasOrderPendingOrProcessing($customer)) {

                $this->customerRepository->delete($id);
            } else {

                session()->flash('error', trans('admin::app.response.order-pending', ['name' => 'Customer']));
                return response()->json(['message' => false], 400);
            }

            session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Customer']));
            return response()->json(['message' => true], 200);
        } catch (\Exception $e) {

            session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Customer']));
        }

        return response()->json(['message' => false], 400);
    }

    /**
     * To load the note taking screen for the customers
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function createNote($id)
    {
        $customer = $this->customerRepository->find($id);

        return view('admin::customers.note')->with('customer', $customer);
    }

    /**
     * To store the response of the note in storage
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function storeNote()
    {
        $this->validate(request(), [
            'notes' => 'string|nullable',
        ]);

        $customer = $this->customerRepository->find(request()->input('_customer'));

        $noteTaken = $customer->update(['notes' => request()->input('notes')]);

        if ($noteTaken) {
            session()->flash('success', 'Note taken');
        } else {
            session()->flash('error', 'Note cannot be taken');
        }

        return redirect()->route('admin.customer.index');
    }

    /**
     * To mass update the customer
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function massUpdate()
    {
        $customerIds = explode(',', request()->input('indexes'));
        $updateOption = request()->input('update-options');

        foreach ($customerIds as $customerId) {
            $customer = $this->customerRepository->find($customerId);

            $customer->update(['status' => $updateOption]);
        }

        session()->flash('success', trans('admin::app.customers.customers.mass-update-success'));

        return redirect()->back();
    }

    /**
     * To mass delete the customer
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function massDestroy()
    {
        $customerIds = explode(',', request()->input('indexes'));

        if (!$this->customerRepository->checkBulkCustomerIfTheyHaveOrderPendingOrProcessing($customerIds)) {

            foreach ($customerIds as $customerId) {
                $this->customerRepository->deleteWhere(['id' => $customerId]);
            }

            session()->flash('success', trans('admin::app.customers.customers.mass-destroy-success'));

            return redirect()->back();
        }

        session()->flash('error', trans('admin::app.response.order-pending', ['name' => 'Customers']));
        return redirect()->back();
    }
}
