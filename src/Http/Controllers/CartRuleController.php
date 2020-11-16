<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Exception;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\CartRuleRepository;
use BagistoPackages\Shop\Repositories\CartRuleCouponRepository;

class CartRuleController extends Controller
{
    /**
     * To hold Cart repository instance
     *
     * @var CartRuleRepository
     */
    protected $cartRuleRepository;

    /**
     * To hold CartRuleCouponRepository repository instance
     *
     * @var CartRuleCouponRepository
     */
    protected $cartRuleCouponRepository;

    /**
     * Create a new controller instance.
     *
     * @param CartRuleRepository $cartRuleRepository
     * @param CartRuleCouponRepository $cartRuleCouponRepository
     *
     * @return void
     */
    public function __construct(CartRuleRepository $cartRuleRepository, CartRuleCouponRepository $cartRuleCouponRepository)
    {
        $this->cartRuleRepository = $cartRuleRepository;
        $this->cartRuleCouponRepository = $cartRuleCouponRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin::promotions.cart-rules.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin::promotions.cart-rules.create');
    }

    /**
     * Copy a given Cart Rule id. Always make the copy is inactive so the
     * user is able to configure it before setting it live.
     * @param int $cartRuleId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function copy(int $cartRuleId)
    {
        $originalCartRule = $this->cartRuleRepository
            ->findOrFail($cartRuleId)
            ->load('channels')
            ->load('customer_groups');

        $copiedCartRule = $originalCartRule
            ->replicate()
            ->fill([
                'status' => 0,
                'name' => __('admin::app.copy-of') . $originalCartRule->name,
            ]);

        $copiedCartRule->save();

        foreach ($copiedCartRule->channels as $channel) {
            $copiedCartRule->channels()->save($channel);
        }

        foreach ($copiedCartRule->customer_groups as $group) {
            $copiedCartRule->customer_groups()->save($group);
        }

        return view('admin::promotions.cart-rules.edit', [
            'cartRule' => $copiedCartRule,
        ]);
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
            'name' => 'required',
            'channels' => 'required|array|min:1',
            'customer_groups' => 'required|array|min:1',
            'coupon_type' => 'required',
            'use_auto_generation' => 'required_if:coupon_type,==,1',
            'coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code',
            'starts_from' => 'nullable|date',
            'ends_till' => 'nullable|date|after_or_equal:starts_from',
            'action_type' => 'required',
            'discount_amount' => 'required|numeric',
        ]);

        $data = request()->all();

        Event::dispatch('promotions.cart_rule.create.before');

        $cartRule = $this->cartRuleRepository->create($data);

        Event::dispatch('promotions.cart_rule.create.after', $cartRule);

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Cart Rule']));

        return redirect()->route('admin.cart-rules.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $cartRule = $this->cartRuleRepository->findOrFail($id);

        return view('admin::promotions.cart-rules.edit', compact('cartRule'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update(Request $request, $id)
    {
        $this->validate(request(), [
            'name' => 'required',
            'channels' => 'required|array|min:1',
            'customer_groups' => 'required|array|min:1',
            'coupon_type' => 'required',
            'use_auto_generation' => 'required_if:coupon_type,==,1',
            'coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code,' . $id,
            'starts_from' => 'nullable|date',
            'ends_till' => 'nullable|date|after_or_equal:starts_from',
            'action_type' => 'required',
            'discount_amount' => 'required|numeric',
        ]);

        $cartRule = $this->cartRuleRepository->findOrFail($id);

        Event::dispatch('promotions.cart_rule.update.before', $cartRule);

        $cartRule = $this->cartRuleRepository->update(request()->all(), $id);

        Event::dispatch('promotions.cart_rule.update.after', $cartRule);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Cart Rule']));

        return redirect()->route('admin.cart-rules.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function destroy($id)
    {
        $cartRule = $this->cartRuleRepository->findOrFail($id);

        try {
            Event::dispatch('promotions.cart_rule.delete.before', $id);

            $this->cartRuleRepository->delete($id);

            Event::dispatch('promotions.cart_rule.delete.after', $id);

            session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Cart Rule']));

            return response()->json(['message' => true], 200);
        } catch (Exception $e) {
            session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Cart Rule']));
        }

        return response()->json(['message' => false], 400);
    }

    /**
     * Generate coupon code for cart rule
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function generateCoupons()
    {
        $this->validate(request(), [
            'coupon_qty' => 'required|integer|min:1',
            'code_length' => 'required|integer|min:10',
            'code_format' => 'required',
        ]);

        if (!request('id')) {
            return response()->json(['message' => trans('admin::app.promotions.cart-rules.cart-rule-not-defind-error')], 400);
        }

        $this->cartRuleCouponRepository->generateCoupons(request()->all(), request('id'));

        return response()->json(['message' => trans('admin::app.response.create-success', ['name' => 'Cart rule coupons'])]);
    }
}
