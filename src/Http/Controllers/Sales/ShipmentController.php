<?php

namespace BagistoPackages\Admin\Http\Controllers\Sales;

use BagistoPackages\Admin\Http\Controllers\Controller;
use BagistoPackages\Shop\Repositories\OrderRepository;
use BagistoPackages\Shop\Repositories\OrderItemRepository;
use BagistoPackages\Shop\Repositories\ShipmentRepository;

class ShipmentController extends Controller
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
     * ShipmentRepository object
     *
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * Create a new controller instance.
     *
     * @param ShipmentRepository $shipmentRepository
     * @param OrderRepository $orderRepository
     * @param  \BagistoPackages\Shop\Repositories\OrderitemRepository  $orderItemRepository
     * @return void
     */
    public function __construct(
        ShipmentRepository $shipmentRepository,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository
    )
    {
        $this->middleware('admin');

        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::sales.shipments.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param int $orderId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function create($orderId)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (! $order->channel || !$order->canShip()) {
            session()->flash('error', trans('admin::app.sales.shipments.creation-error'));

            return redirect()->back();
        }

        return view('admin::sales.shipments.create', compact('order'));
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

        if (! $order->canShip()) {
            session()->flash('error', trans('admin::app.sales.shipments.order-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'shipment.source'        => 'required',
            'shipment.items.*.*'     => 'required|numeric|min:0',
        ]);

        $data = request()->all();

        if (! $this->isInventoryValidate($data)) {
            session()->flash('error', trans('admin::app.sales.shipments.quantity-invalid'));

            return redirect()->back();
        }

        $this->shipmentRepository->create(array_merge($data, ['order_id' => $orderId]));

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Shipment']));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    /**
     * Checks if requested quantity available or not
     *
     * @param array $data
     * @return bool
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function isInventoryValidate(&$data)
    {
        if (! isset($data['shipment']['items'])) {
            return ;
        }

        $valid = false;

        $inventorySourceId = $data['shipment']['source'];

        foreach ($data['shipment']['items'] as $itemId => $inventorySource) {
            if ($qty = $inventorySource[$inventorySourceId]) {
                $orderItem = $this->orderItemRepository->find($itemId);

                if ($orderItem->qty_to_ship < $qty) {
                    return false;
                }

                if ($orderItem->getTypeInstance()->isComposite()) {
                    foreach ($orderItem->children as $child) {
                        if (! $child->qty_ordered) {
                            continue;
                        }

                        $finalQty = ($child->qty_ordered / $orderItem->qty_ordered) * $qty;

                        $availableQty = $child->product->inventories()
                            ->where('inventory_source_id', $inventorySourceId)
                            ->sum('qty');

                        if ($child->qty_to_ship < $finalQty || $availableQty < $finalQty) {
                            return false;
                        }
                    }
                } else {
                    $availableQty = $orderItem->product->inventories()
                            ->where('inventory_source_id', $inventorySourceId)
                            ->sum('qty');

                    if ($orderItem->qty_to_ship < $qty || $availableQty < $qty) {
                        return false;
                    }
                }

                $valid = true;
            } else {
                unset($data['shipment']['items'][$itemId]);
            }
        }

        return $valid;
    }

    /**
     * Show the view for the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function view($id)
    {
        $shipment = $this->shipmentRepository->findOrFail($id);

        return view('admin::sales.shipments.view', compact('shipment'));
    }
}
