<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\TaxCategoryRepository;
use BagistoPackages\Shop\Repositories\TaxRateRepository;

class TaxCategoryController extends Controller
{
    /**
     * TaxCategoryRepository
     *
     * @var TaxCategoryRepository
     */
    protected $taxCategoryRepository;

    /**
     * TaxRateRepository
     *
     * @var TaxRateRepository
     */
    protected $taxRateRepository;

    /**
     * Create a new controller instance.
     *
     * @param TaxCategoryRepository $taxCategoryRepository
     * @param TaxRateRepository $taxRateRepository
     * @return void
     */
    public function __construct(TaxCategoryRepository $taxCategoryRepository, TaxRateRepository $taxRateRepository)
    {
        $this->taxCategoryRepository = $taxCategoryRepository;
        $this->taxRateRepository = $taxRateRepository;
    }

    /**
     * Function to show the tax category form
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function show()
    {
        return view('admin::tax.tax-categories.create')->with('taxRates', $this->taxRateRepository->all());
    }

    /**
     * Function to create the tax category.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function create()
    {
        $data = request()->input();

        $this->validate(request(), [
            'code' => 'required|string|unique:tax_categories,code',
            'name' => 'required|string',
            'description' => 'required|string',
            'taxrates' => 'array|required',
        ]);

        Event::dispatch('tax.tax_category.create.before');

        $taxCategory = $this->taxCategoryRepository->create($data);

        //attach the categories in the tax map table
        $this->taxCategoryRepository->attachOrDetach($taxCategory, $data['taxrates']);

        Event::dispatch('tax.tax_category.create.after', $taxCategory);

        session()->flash('success', trans('admin::app.settings.tax-categories.create-success'));

        return redirect()->route('admin.tax-categories.index');
    }

    /**
     * To show the edit form form the tax category
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $taxCategory = $this->taxCategoryRepository->findOrFail($id);

        return view('admin::tax.tax-categories.edit', compact('taxCategory'));
    }

    /**
     * To update the tax category
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update($id)
    {
        $this->validate(request(), [
            'code' => 'required|string|unique:tax_categories,code,' . $id,
            'name' => 'required|string',
            'description' => 'required|string',
            'taxrates' => 'array|required',
        ]);

        $data = request()->input();

        Event::dispatch('tax.tax_category.update.before', $id);

        $taxCategory = $this->taxCategoryRepository->update($data, $id);

        Event::dispatch('tax.tax_category.update.after', $taxCategory);

        if (!$taxCategory) {
            session()->flash('error', trans('admin::app.settings.tax-categories.update-error'));

            return redirect()->back();
        }

        $taxRates = $data['taxrates'];

        //attach the categories in the tax map table
        $this->taxCategoryRepository->attachOrDetach($taxCategory, $taxRates);

        session()->flash('success', trans('admin::app.settings.tax-categories.update-success'));

        return redirect()->route('admin.tax-categories.index');
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
        $taxCategory = $this->taxCategoryRepository->findOrFail($id);

        try {
            Event::dispatch('tax.tax_category.delete.before', $id);

            $this->taxCategoryRepository->delete($id);

            Event::dispatch('tax.tax_category.delete.after', $id);

            session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Tax Category']));

            return response()->json(['message' => true], 200);
        } catch (Exception $e) {
            session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Tax Category']));
        }

        return response()->json(['message' => false], 400);
    }
}
