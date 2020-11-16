<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\CatalogRuleRepository;
use BagistoPackages\Shop\Helpers\CatalogRuleIndex;

class CatalogRuleController extends Controller
{
    /**
     * To hold Catalog repository instance
     *
     * @var CatalogRuleRepository
     */
    protected $catalogRuleRepository;

    /**
     * CatalogRuleIndex
     *
     * @var CatalogRuleIndex
     */
    protected $catalogRuleIndexHelper;

    /**
     * Create a new controller instance.
     *
     * @param CatalogRuleRepository $catalogRuleRepository
     * @param CatalogRuleIndex $catalogRuleIndexHelper
     * @return void
     */
    public function __construct(CatalogRuleRepository $catalogRuleRepository, CatalogRuleIndex $catalogRuleIndexHelper)
    {
        $this->catalogRuleRepository = $catalogRuleRepository;
        $this->catalogRuleIndexHelper = $catalogRuleIndexHelper;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin::promotions.catalog-rules.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin::promotions.catalog-rules.create');
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
            'starts_from' => 'nullable|date',
            'ends_till' => 'nullable|date|after_or_equal:starts_from',
            'action_type' => 'required',
            'discount_amount' => 'required|numeric',
        ]);

        $data = request()->all();

        Event::dispatch('promotions.catalog_rule.create.before');

        $catalogRule = $this->catalogRuleRepository->create($data);

        Event::dispatch('promotions.catalog_rule.create.after', $catalogRule);

        $this->catalogRuleIndexHelper->reindexComplete();

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Catalog Rule']));

        return redirect()->route('admin.catalog-rules.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $catalogRule = $this->catalogRuleRepository->findOrFail($id);

        return view('admin::promotions.catalog-rules.edit', compact('catalogRule'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
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
            'starts_from' => 'nullable|date',
            'ends_till' => 'nullable|date|after_or_equal:starts_from',
            'action_type' => 'required',
            'discount_amount' => 'required|numeric',
        ]);

        $catalogRule = $this->catalogRuleRepository->findOrFail($id);

        Event::dispatch('promotions.catalog_rule.update.before', $catalogRule);

        $catalogRule = $this->catalogRuleRepository->update(request()->all(), $id);

        Event::dispatch('promotions.catalog_rule.update.after', $catalogRule);

        $this->catalogRuleIndexHelper->reindexComplete();

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Catalog Rule']));

        return redirect()->route('admin.catalog-rules.index');
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
        $catalogRule = $this->catalogRuleRepository->findOrFail($id);

        try {
            Event::dispatch('promotions.catalog_rule.delete.before', $id);

            $this->catalogRuleRepository->delete($id);

            Event::dispatch('promotions.catalog_rule.delete.after', $id);

            session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Catalog Rule']));

            return response()->json(['message' => true], 200);
        } catch (\Exception $e) {
            session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Catalog Rule']));
        }

        return response()->json(['message' => false], 400);
    }
}
