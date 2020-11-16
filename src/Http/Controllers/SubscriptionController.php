<?php

namespace BagistoPackages\Admin\Http\Controllers;

use BagistoPackages\Shop\Repositories\SubscribersListRepository;

class SubscriptionController extends Controller
{
    /**
     * SubscribersListRepository
     *
     * @var SubscribersListRepository
     */
    protected $subscribersListRepository;

    /**
     * Create a new controller instance.
     *
     * @param SubscribersListRepository $subscribersListRepository
     */
    public function __construct(SubscribersListRepository $subscribersListRepository)
    {
        $this->subscribersListRepository = $subscribersListRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::customers.subscribers.index');
    }

    /**
     * To unsubscribe the user without deleting the resource of the subscribed user
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $subscriber = $this->subscribersListRepository->findOrFail($id);

        return view('admin::customers.subscribers.edit')->with('subscriber', $subscriber);
    }

    /**
     * To unsubscribe the user without deleting the resource of the subscribed user
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function update($id)
    {
        $data = request()->all();

        $subscriber = $this->subscribersListRepository->findOrFail($id);

        $result = $subscriber->update($data);

        if ($result) {
            session()->flash('success', trans('admin::app.customers.subscribers.update-success'));
        } else {
            session()->flash('error', trans('admin::app.customers.subscribers.update-failed'));
        }

        return redirect()->route('admin.customers.subscribers.index');
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
        $subscriber = $this->subscribersListRepository->findOrFail($id);

        try {
            $this->subscribersListRepository->delete($id);

            session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Subscriber']));

            return response()->json(['message' => true], 200);
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Subscriber']));
        }

        return response()->json(['message' => false], 400);
    }
}
