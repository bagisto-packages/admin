<?php

namespace BagistoPackages\Admin\Http\Controllers\Sales;

use BagistoPackages\Admin\Http\Controllers\Controller;
use BagistoPackages\Shop\Repositories\OrderRepository;
use BagistoPackages\Shop\Repositories\OrderItemRepository;
use BagistoPackages\Shop\Repositories\RefundRepository;

class RefundController extends Controller
{
    /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * OrderItemRepository object
     *
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * RefundRepository object
     *
     * @var RefundRepository
     */
    protected $refundRepository;

    /**
     * Create a new controller instance.
     *
     * @param OrderRepository $orderRepository
     * @param OrderItemRepository $orderItemRepository
     * @param RefundRepository $refundRepository
     * @return void
     */
    public function __construct(
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        RefundRepository $refundRepository
    )
    {
        $this->middleware('admin');

        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->refundRepository = $refundRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\View
     */
    public function index()
    {
        return view('admin::sales.refunds.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param int $orderId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function create($orderId)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        return view('admin::sales.refunds.create', compact('order'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function store($orderId)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (! $order->canRefund()) {
            session()->flash('error', trans('admin::app.sales.refunds.creation-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'refund.items.*' => 'required|numeric|min:0',
        ]);

        $data = request()->all();

        if (! $data['refund']['shipping']) {
            $data['refund']['shipping'] = 0;
        }

        $totals = $this->refundRepository->getOrderItemsRefundSummary($data['refund']['items'], $orderId);

        $maxRefundAmount = $totals['grand_total']['price'] - $order->refunds()->sum('base_adjustment_refund');

        $refundAmount = $totals['grand_total']['price'] - $totals['shipping']['price'] + $data['refund']['shipping'] + $data['refund']['adjustment_refund'] - $data['refund']['adjustment_fee'];

        if (! $refundAmount) {
            session()->flash('error', trans('admin::app.sales.refunds.invalid-refund-amount-error'));

            return redirect()->back();
        }

        if ($refundAmount > $maxRefundAmount) {
            session()->flash('error', trans('admin::app.sales.refunds.refund-limit-error', ['amount' => core()->formatBasePrice($maxRefundAmount)]));

            return redirect()->back();
        }

        $this->refundRepository->create(array_merge($data, ['order_id' => $orderId]));

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Refund']));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $orderId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function updateQty($orderId)
    {
        $data = $this->refundRepository->getOrderItemsRefundSummary(request()->all(), $orderId);

        if (! $data) {
            return response('');
        }

        return response()->json($data);
    }

    /**
     * Show the view for the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function view($id)
    {
        $refund = $this->refundRepository->findOrFail($id);

        return view('admin::sales.refunds.view', compact('refund'));
    }
}
