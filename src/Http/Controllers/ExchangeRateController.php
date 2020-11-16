<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\ExchangeRateRepository;
use BagistoPackages\Shop\Repositories\CurrencyRepository;

class ExchangeRateController extends Controller
{
    /**
     * ExchangeRateRepository instance
     *
     * @var ExchangeRateRepository
     */
    protected $exchangeRateRepository;

    /**
     * CurrencyRepository object
     *
     * @var CurrencyRepository
     */
    protected $currencyRepository;

    /**
     * Create a new controller instance.
     *
     * @param ExchangeRateRepository $exchangeRateRepository
     * @param CurrencyRepository $currencyRepository
     * @return void
     */
    public function __construct(
        ExchangeRateRepository $exchangeRateRepository,
        CurrencyRepository $currencyRepository
    )
    {
        $this->exchangeRateRepository = $exchangeRateRepository;
        $this->currencyRepository = $currencyRepository;
        $this->exchangeRateRepository = $exchangeRateRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::settings.exchange_rates.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        $currencies = $this->currencyRepository->with('exchange_rate')->all();

        return view('admin::settings.exchange_rates.create', compact('currencies'));
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
            'target_currency' => ['required', 'unique:currency_exchange_rates,target_currency'],
            'rate' => 'required|numeric',
        ]);

        Event::dispatch('core.exchange_rate.create.before');

        $exchangeRate = $this->exchangeRateRepository->create(request()->all());

        Event::dispatch('core.exchange_rate.create.after', $exchangeRate);

        session()->flash('success', trans('admin::app.settings.exchange_rates.create-success'));

        return redirect()->route('admin.exchange_rates.index');
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
        $currencies = $this->currencyRepository->all();

        $exchangeRate = $this->exchangeRateRepository->findOrFail($id);

        return view('admin::settings.exchange_rates.edit', compact('currencies', 'exchangeRate'));
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
            'target_currency' => ['required', 'unique:currency_exchange_rates,target_currency,' . $id],
            'rate' => 'required|numeric',
        ]);

        Event::dispatch('core.exchange_rate.update.before', $id);

        $exchangeRate = $this->exchangeRateRepository->update(request()->all(), $id);

        Event::dispatch('core.exchange_rate.update.after', $exchangeRate);

        session()->flash('success', trans('admin::app.settings.exchange_rates.update-success'));

        return redirect()->route('admin.exchange_rates.index');
    }

    /**
     * Update Rates Using Exchange Rates API
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRates()
    {
        try {
            app(config('services.exchange-api.' . config('services.exchange-api.default') . '.class'))->updateRates();

            session()->flash('success', trans('admin::app.settings.exchange_rates.update-success'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->back();
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
        $exchangeRate = $this->exchangeRateRepository->findOrFail($id);

        if ($this->exchangeRateRepository->count() == 1) {
            session()->flash('error', trans('admin::app.settings.exchange_rates.last-delete-error'));
        } else {
            try {
                Event::dispatch('core.exchange_rate.delete.before', $id);

                $this->exchangeRateRepository->delete($id);

                session()->flash('success', trans('admin::app.settings.exchange_rates.delete-success'));

                Event::dispatch('core.exchange_rate.delete.after', $id);

                return response()->json(['message' => true], 200);
            } catch (\Exception $e) {
                report($e);

                session()->flash('error', trans('admin::app.response.delete-error', ['name' => 'Exchange rate']));
            }
        }

        return response()->json(['message' => false], 400);
    }
}
