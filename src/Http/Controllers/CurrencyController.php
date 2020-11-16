<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\CurrencyRepository;

class CurrencyController extends Controller
{
    /**
     * CurrencyRepository object
     *
     * @var CurrencyRepository
     */
    protected $currencyRepository;

    /**
     * Create a new controller instance.
     *
     * @param CurrencyRepository $currencyRepository
     * @return void
     */
    public function __construct(CurrencyRepository $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::settings.currencies.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.currencies.create');
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
            'code' => 'required|min:3|max:3|unique:currencies,code',
            'name' => 'required',
        ]);

        Event::dispatch('core.currency.create.before');

        $currency = $this->currencyRepository->create(request()->all());

        Event::dispatch('core.currency.create.after', $currency);

        session()->flash('success', trans('admin::app.settings.currencies.create-success'));

        return redirect()->route('admin.currencies.index');
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
        $currency = $this->currencyRepository->findOrFail($id);

        return view('admin::settings.currencies.edit', compact('currency'));
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
            'code' => ['required', 'unique:currencies,code,' . $id, new \BagistoPackages\Shop\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        Event::dispatch('core.currency.update.before', $id);

        $currency = $this->currencyRepository->update(request()->all(), $id);

        Event::dispatch('core.currency.update.after', $currency);

        session()->flash('success', trans('admin::app.settings.currencies.update-success'));

        return redirect()->route('admin.currencies.index');
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
        $currency = $this->currencyRepository->findOrFail($id);

        if ($this->currencyRepository->count() == 1) {
            session()->flash('warning', trans('admin::app.settings.currencies.last-delete-error'));
        } else {
            try {
                Event::dispatch('core.currency.delete.before', $id);

                $this->currencyRepository->delete($id);

                Event::dispatch('core.currency.delete.after', $id);

                session()->flash('success', trans('admin::app.settings.currencies.delete-success'));

                return response()->json(['message' => true], 200);
            } catch (\Exception $e) {
                report($e);
                session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Currency']));
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

        if (request()->isMethod('post')) {
            $indexes = explode(',', request()->input('indexes'));

            foreach ($indexes as $key => $value) {
                try {
                    Event::dispatch('core.currency.delete.before', $value);

                    $this->currencyRepository->delete($value);

                    Event::dispatch('core.currency.delete.after', $value);
                } catch (\Exception $e) {
                    $suppressFlash = true;

                    continue;
                }
            }

            if (!$suppressFlash)
                session()->flash('success', trans('admin::app.datagrid.mass-ops.delete-success', ['resource' => 'currencies']));
            else
                session()->flash('info', trans('admin::app.datagrid.mass-ops.partial-action', ['resource' => 'currencies']));

            return redirect()->back();
        } else {
            session()->flash('error', trans('admin::app.datagrid.mass-ops.method-error'));

            return redirect()->back();
        }
    }
}
