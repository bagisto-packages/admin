<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\LocaleRepository;

class LocaleController extends Controller
{
    /**
     * LocaleRepository object
     *
     * @var LocaleRepository
     */
    protected $localeRepository;

    /**
     * Create a new controller instance.
     *
     * @param LocaleRepository $localeRepository
     * @return void
     */
    public function __construct(LocaleRepository $localeRepository)
    {
        $this->localeRepository = $localeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::settings.locales.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.locales.create');
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
            'code' => ['required', 'unique:locales,code', new \BagistoPackages\Shop\Contracts\Validations\Code],
            'name' => 'required',
            'direction' => 'in:ltr,rtl',
        ]);

        Event::dispatch('core.locale.create.before');

        $locale = $this->localeRepository->create(request()->all());

        Event::dispatch('core.locale.create.after', $locale);

        session()->flash('success', trans('admin::app.settings.locales.create-success'));

        return redirect()->route('admin.locales.index');
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
        $locale = $this->localeRepository->findOrFail($id);

        return view('admin::settings.locales.edit', compact('locale'));
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
            'code' => ['required', 'unique:locales,code,' . $id, new \BagistoPackages\Shop\Contracts\Validations\Code],
            'name' => 'required',
            'direction' => 'in:ltr,rtl',
        ]);

        Event::dispatch('core.locale.update.before', $id);

        $locale = $this->localeRepository->update(request()->all(), $id);

        Event::dispatch('core.locale.update.after', $locale);

        session()->flash('success', trans('admin::app.settings.locales.update-success'));

        return redirect()->route('admin.locales.index');
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
        $locale = $this->localeRepository->findOrFail($id);

        if ($this->localeRepository->count() == 1) {
            session()->flash('warning', trans('admin::app.settings.locales.last-delete-error'));
        } else {
            try {
                Event::dispatch('core.locale.delete.before', $id);

                $this->localeRepository->delete($id);

                Event::dispatch('core.locale.delete.after', $id);

                session()->flash('success', trans('admin::app.settings.locales.delete-success'));

                return response()->json(['message' => true], 200);
            } catch (\Exception $e) {
                session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Locale']));
            }
        }

        return response()->json(['message' => false], 400);
    }
}
