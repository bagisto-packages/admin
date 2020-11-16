<?php

namespace BagistoPackages\Admin\Http\Controllers;

use BagistoPackages\Shop\Repositories\AttributeFamilyRepository;
use BagistoPackages\Shop\Repositories\AttributeRepository;

class AttributeFamilyController extends Controller
{
    /**
     * AttributeFamilyRepository object
     *
     * @var AttributeFamilyRepository
     */
    protected $attributeFamilyRepository;

    /**
     * AttributeRepository object
     *
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * Create a new controller instance.
     *
     * @param AttributeFamilyRepository $attributeFamilyRepository
     * @param AttributeRepository $attributeRepository
     * @return void
     */
    public function __construct(AttributeFamilyRepository $attributeFamilyRepository, AttributeRepository $attributeRepository)
    {
        $this->attributeFamilyRepository = $attributeFamilyRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::catalog.families.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        $attributeFamily = $this->attributeFamilyRepository->with(['attribute_groups.custom_attributes'])->findOneByField('code', 'default');

        $custom_attributes = $this->attributeRepository->all(['id', 'code', 'admin_name', 'type']);

        return view('admin::catalog.families.create', compact('custom_attributes', 'attributeFamily'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function store()
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code', new \BagistoPackages\Shop\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $attributeFamily = $this->attributeFamilyRepository->create(request()->all());

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Family']));

        return redirect()->route('admin.catalog.families.index');
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
        $attributeFamily = $this->attributeFamilyRepository->with(['attribute_groups.custom_attributes'])->findOrFail($id, ['*']);

        $custom_attributes = $this->attributeRepository->all(['id', 'code', 'admin_name', 'type']);

        return view('admin::catalog.families.edit', compact('attributeFamily', 'custom_attributes'));
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
            'code' => ['required', 'unique:attribute_families,code,' . $id, new \BagistoPackages\Shop\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $attributeFamily = $this->attributeFamilyRepository->update(request()->all(), $id);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Family']));

        return redirect()->route('admin.catalog.families.index');
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
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        if ($this->attributeFamilyRepository->count() == 1) {
            session()->flash('error', trans('admin::app.response.last-delete-error', ['name' => 'Family']));

        } elseif ($attributeFamily->products()->count()) {
            session()->flash('error', trans('admin::app.response.attribute-product-error', ['name' => 'Attribute family']));
        } else {
            try {
                $this->attributeFamilyRepository->delete($id);

                session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Family']));

                return response()->json(['message' => true], 200);
            } catch (\Exception $e) {
                report($e);
                session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Family']));
            }
        }

        return response()->json(['message' => false], 400);
    }

    /**
     * Remove the specified resources from database
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function massDestroy()
    {
        $suppressFlash = false;

        if (request()->isMethod('delete')) {
            $indexes = explode(',', request()->input('indexes'));

            foreach ($indexes as $key => $value) {
                try {
                    $this->attributeFamilyRepository->delete($value);
                } catch (\Exception $e) {
                    report($e);
                    $suppressFlash = true;

                    continue;
                }
            }

            if (!$suppressFlash) {
                session()->flash('success', ('admin::app.datagrid.mass-ops.delete-success'));
            } else {
                session()->flash('info', trans('admin::app.datagrid.mass-ops.partial-action', ['resource' => 'Attribute Family']));
            }

            return redirect()->back();
        } else {
            session()->flash('error', trans('admin::app.datagrid.mass-ops.method-error'));

            return redirect()->back();
        }
    }
}
