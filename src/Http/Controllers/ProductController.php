<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Exception;
use BagistoPackages\Shop\Models\Product;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use BagistoPackages\Shop\Helpers\ProductType;
use BagistoPackages\Shop\Contracts\Validations\Slug;
use BagistoPackages\Shop\Http\Requests\ProductForm;
use BagistoPackages\Shop\Repositories\ProductRepository;
use BagistoPackages\Shop\Repositories\CategoryRepository;
use BagistoPackages\Shop\Repositories\AttributeFamilyRepository;
use BagistoPackages\Shop\Repositories\InventorySourceRepository;
use BagistoPackages\Shop\Repositories\ProductDownloadableLinkRepository;
use BagistoPackages\Shop\Repositories\ProductDownloadableSampleRepository;
use BagistoPackages\Shop\Repositories\ProductAttributeValueRepository;

class ProductController extends Controller
{
    /**
     * CategoryRepository object
     *
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * ProductRepository object
     *
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * ProductDownloadableLinkRepository object
     *
     * @var ProductDownloadableLinkRepository
     */
    protected $productDownloadableLinkRepository;

    /**
     * ProductDownloadableSampleRepository object
     *
     * @var ProductDownloadableSampleRepository
     */
    protected $productDownloadableSampleRepository;

    /**
     * AttributeFamilyRepository object
     *
     * @var AttributeFamilyRepository
     */
    protected $attributeFamilyRepository;

    /**
     * InventorySourceRepository object
     *
     * @var InventorySourceRepository
     */
    protected $inventorySourceRepository;

    /**
     * ProductAttributeValueRepository object
     *
     * @var ProductAttributeValueRepository
     */
    protected $productAttributeValueRepository;

    /**
     * Create a new controller instance.
     *
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param ProductDownloadableLinkRepository $productDownloadableLinkRepository
     * @param ProductDownloadableSampleRepository $productDownloadableSampleRepository
     * @param AttributeFamilyRepository $attributeFamilyRepository
     * @param InventorySourceRepository $inventorySourceRepository
     * @param ProductAttributeValueRepository $productAttributeValueRepository
     *
     * @return void
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ProductDownloadableLinkRepository $productDownloadableLinkRepository,
        ProductDownloadableSampleRepository $productDownloadableSampleRepository,
        AttributeFamilyRepository $attributeFamilyRepository,
        InventorySourceRepository $inventorySourceRepository,
        ProductAttributeValueRepository $productAttributeValueRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->productDownloadableLinkRepository = $productDownloadableLinkRepository;
        $this->productDownloadableSampleRepository = $productDownloadableSampleRepository;
        $this->attributeFamilyRepository = $attributeFamilyRepository;
        $this->inventorySourceRepository = $inventorySourceRepository;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::catalog.products.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function create()
    {
        $families = $this->attributeFamilyRepository->all();

        $configurableFamily = null;

        if ($familyId = request()->get('family')) {
            $configurableFamily = $this->attributeFamilyRepository->find($familyId);
        }

        return view('admin::catalog.products.create', compact('families', 'configurableFamily'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store()
    {
        if (!request()->get('family')
            && ProductType::hasVariants(request()->input('type'))
            && request()->input('sku') != ''
        ) {
            return redirect(url()->current() . '?type=' . request()->input('type') . '&family=' . request()->input('attribute_family_id') . '&sku=' . request()->input('sku'));
        }

        if (ProductType::hasVariants(request()->input('type'))
            && (!request()->has('super_attributes')
                || !count(request()->get('super_attributes')))
        ) {
            session()->flash('error', trans('admin::app.catalog.products.configurable-error'));

            return back();
        }

        $this->validate(request(), [
            'type' => 'required',
            'attribute_family_id' => 'required',
            'sku' => ['required', 'unique:products,sku', new Slug],
        ]);

        $product = $this->productRepository->create(request()->all());

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Product']));

        return redirect()->route('admin.catalog.products.edit', ['id' => $product->id]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $product = $this->productRepository->with(['variants', 'variants.inventories'])->findOrFail($id);

        $categories = $this->categoryRepository->getCategoryTree();

        $inventorySources = $this->inventorySourceRepository->findWhere(['status' => 1]);

        return view('admin::catalog.products.edit', compact('product', 'categories', 'inventorySources'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProductForm $request
     * @param int $id
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function update(ProductForm $request, $id)
    {
        $data = request()->all();

        $multiselectAttributeCodes = array();

        $productAttributes = $this->productRepository->findOrFail($id);

        foreach ($productAttributes->attribute_family->attribute_groups as $attributeGroup) {
            $customAttributes = $productAttributes->getEditableAttributes($attributeGroup);

            if (count($customAttributes)) {
                foreach ($customAttributes as $attribute) {
                    if ($attribute->type == 'multiselect') {
                        array_push($multiselectAttributeCodes, $attribute->code);
                    }
                }
            }
        }

        if (count($multiselectAttributeCodes)) {
            foreach ($multiselectAttributeCodes as $multiselectAttributeCode) {
                if (!isset($data[$multiselectAttributeCode])) {
                    $data[$multiselectAttributeCode] = array();
                }
            }
        }

        $product = $this->productRepository->update($data, $id);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Product']));

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Uploads downloadable file
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadLink($id)
    {
        return response()->json(
            $this->productDownloadableLinkRepository->upload(request()->all(), $id)
        );
    }

    /**
     * Copy a given Product.
     * @param int $productId
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function copy(int $productId)
    {
        $originalProduct = $this->productRepository->findOrFail($productId);

        if (!$originalProduct->getTypeInstance()->canBeCopied()) {
            session()->flash('error',
                trans('admin::app.response.product-can-not-be-copied', [
                    'type' => $originalProduct->type,
                ]));

            return redirect()->to(route('admin.catalog.products.index'));
        }

        if ($originalProduct->parent_id) {
            session()->flash('error',
                trans('admin::app.catalog.products.variant-already-exist-message'));

            return redirect()->to(route('admin.catalog.products.index'));
        }

        $copiedProduct = $this->productRepository->copy($originalProduct);

        if ($copiedProduct instanceof Product && $copiedProduct->id) {
            session()->flash('success', trans('admin::app.response.product-copied'));
        } else {
            session()->flash('error', trans('admin::app.response.error-while-copying'));
        }

        return redirect()->to(route('admin.catalog.products.edit', ['id' => $copiedProduct->id]));
    }

    /**
     * Uploads downloadable sample file
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSample($id)
    {
        return response()->json(
            $this->productDownloadableSampleRepository->upload(request()->all(), $id)
        );
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
        $product = $this->productRepository->findOrFail($id);

        try {
            $this->productRepository->delete($id);

            session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Product']));

            return response()->json(['message' => true], 200);
        } catch (Exception $e) {
            report($e);

            session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Product']));
        }

        return response()->json(['message' => false], 400);
    }

    /**
     * Mass Delete the products
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function massDestroy()
    {
        $productIds = explode(',', request()->input('indexes'));

        foreach ($productIds as $productId) {
            $product = $this->productRepository->find($productId);

            if (isset($product)) {
                $this->productRepository->delete($productId);
            }
        }

        session()->flash('success', trans('admin::app.catalog.products.mass-delete-success'));

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Mass updates the products
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function massUpdate()
    {
        $data = request()->all();

        if (!isset($data['massaction-type'])) {
            return redirect()->back();
        }

        if (!$data['massaction-type'] == 'update') {
            return redirect()->back();
        }

        $productIds = explode(',', $data['indexes']);

        foreach ($productIds as $productId) {
            $this->productRepository->update([
                'channel' => null,
                'locale' => null,
                'status' => $data['update-options'],
            ], $productId);
        }

        session()->flash('success', trans('admin::app.catalog.products.mass-update-success'));

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * To be manually invoked when data is seeded into products
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sync()
    {
        Event::dispatch('products.datagrid.sync', true);

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Result of search product.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function productLinkSearch()
    {
        if (request()->ajax()) {
            $results = [];

            foreach ($this->productRepository->searchProductByAttribute(request()->input('query')) as $row) {
                $results[] = [
                    'id' => $row->product_id,
                    'sku' => $row->sku,
                    'name' => $row->name,
                ];
            }

            return response()->json($results);
        } else {
            return view('admin::catalog.products.edit');
        }
    }

    /**
     * Download image or file
     *
     * @param int $productId
     * @param int $attributeId
     *
     * @return \Illuminate\Http\Response
     */
    public function download($productId, $attributeId)
    {
        $productAttribute = $this->productAttributeValueRepository->findOneWhere([
            'product_id' => $productId,
            'attribute_id' => $attributeId,
        ]);

        return Storage::download($productAttribute['text_value']);
    }

    /**
     * Search simple products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchSimpleProducts()
    {
        return response()->json(
            $this->productRepository->searchSimpleProducts(request()->input('query'))
        );
    }
}
