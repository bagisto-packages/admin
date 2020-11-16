<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\ProductReviewRepository;

class ReviewController extends Controller
{
    /**
     * ProductReviewRepository object
     *
     * @var ProductReviewRepository
     */
    protected $productReviewRepository;

    /**
     * Create a new controller instance.
     *
     * @param ProductReviewRepository $productReviewRepository
     */
    public function __construct(ProductReviewRepository $productReviewRepository)
    {
        $this->productReviewRepository = $productReviewRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::customers.reviews.index');
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
        $review = $this->productReviewRepository->findOrFail($id);

        return view('admin::customers.reviews.edit', compact('review'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update($id)
    {
        Event::dispatch('customer.review.update.before', $id);

        $this->productReviewRepository->update(request()->all(), $id);

        Event::dispatch('customer.review.update.after', $id);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Review']));

        return redirect()->route('admin.customer.review.index');
    }

    /**
     * Delete the review of the current product
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function destroy($id)
    {
        $productReview = $this->productReviewRepository->findOrFail($id);

        try {
            Event::dispatch('customer.review.delete.before', $id);

            $this->productReviewRepository->delete($id);

            Event::dispatch('customer.review.delete.after', $id);

            session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Review']));

            return response()->json(['message' => true], 200);
        } catch (\Exception $e) {
            report($e);
            session()->flash('success', trans('admin::app.response.delete-failed', ['name' => 'Review']));
        }

        return response()->json(['message' => false], 400);
    }

    /**
     * Mass delete the reviews on the products.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function massDestroy()
    {
        $suppressFlash = false;

        if (request()->isMethod('post')) {
            $data = request()->all();

            $indexes = explode(',', request()->input('indexes'));

            foreach ($indexes as $key => $value) {
                try {
                    Event::dispatch('customer.review.delete.before', $value);

                    $this->productReviewRepository->delete($value);

                    Event::dispatch('customer.review.delete.after', $value);
                } catch (\Exception $e) {
                    $suppressFlash = true;

                    continue;
                }
            }

            if (!$suppressFlash) {
                session()->flash('success', trans('admin::app.datagrid.mass-ops.delete-success', ['resource' => 'Reviews']));
            } else {
                session()->flash('info', trans('admin::app.datagrid.mass-ops.partial-action', ['resource' => 'Reviews']));
            }

            return redirect()->route('admin.customer.review.index');

        } else {
            session()->flash('error', trans('admin::app.datagrid.mass-ops.method-error'));

            return redirect()->back();
        }
    }

    /**
     * Mass approve the reviews on the products.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function massUpdate()
    {
        $suppressFlash = false;

        if (request()->isMethod('post')) {
            $data = request()->all();

            $indexes = explode(',', request()->input('indexes'));

            foreach ($indexes as $key => $value) {
                $review = $this->productReviewRepository->findOneByField('id', $value);

                try {
                    if ($data['massaction-type'] == 'update') {
                        if ($data['update-options'] == 1) {
                            Event::dispatch('customer.review.update.before', $value);

                            $review->update(['status' => 'approved']);

                            Event::dispatch('customer.review.update.after', $review);
                        } elseif ($data['update-options'] == 0) {
                            $review->update(['status' => 'pending']);
                        } elseif ($data['update-options'] == 2) {
                            $review->update(['status' => 'disapproved']);
                        } else {
                            continue;
                        }
                    }
                } catch (\Exception $e) {
                    $suppressFlash = true;

                    continue;
                }
            }

            if (!$suppressFlash) {
                session()->flash('success', trans('admin::app.datagrid.mass-ops.update-success', ['resource' => 'Reviews']));
            } else {
                session()->flash('info', trans('admin::app.datagrid.mass-ops.partial-action', ['resource' => 'Reviews']));
            }

            return redirect()->route('admin.customer.review.index');
        } else {
            session()->flash('error', trans('admin::app.datagrid.mass-ops.method-error'));

            return redirect()->back();
        }
    }
}
