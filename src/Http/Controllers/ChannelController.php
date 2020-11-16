<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Illuminate\Support\Facades\Event;
use BagistoPackages\Shop\Repositories\ChannelRepository;

class ChannelController extends Controller
{
    /**
     * ChannelRepository object
     *
     * @var ChannelRepository
     */
    protected $channelRepository;

    /**
     * Create a new controller instance.
     *
     * @param ChannelRepository $channelRepository
     * @return void
     */
    public function __construct(ChannelRepository $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin::settings.channels.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.channels.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store()
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:channels,code', new \BagistoPackages\Shop\Contracts\Validations\Code],
            'name' => 'required',
            'locales' => 'required|array|min:1',
            'default_locale_id' => 'required|in_array:locales.*',
            'currencies' => 'required|array|min:1',
            'base_currency_id' => 'required|in_array:currencies.*',
            'root_category_id' => 'required',
            'logo.*' => 'mimes:bmp,jpeg,jpg,png,webp',
            'favicon.*' => 'mimes:bmp,jpeg,jpg,png,webp',
            'seo_title' => 'required|string',
            'seo_description' => 'required|string',
            'seo_keywords' => 'required|string',
            'hostname' => 'unique:channels,hostname',
        ]);

        $data = request()->all();

        $data['seo']['meta_title'] = $data['seo_title'];
        $data['seo']['meta_description'] = $data['seo_description'];
        $data['seo']['meta_keywords'] = $data['seo_keywords'];

        unset($data['seo_title']);
        unset($data['seo_description']);
        unset($data['seo_keywords']);

        $data['home_seo'] = json_encode($data['seo']);

        unset($data['seo']);

        Event::dispatch('core.channel.create.before');

        $channel = $this->channelRepository->create($data);

        Event::dispatch('core.channel.create.after', $channel);

        session()->flash('success', trans('admin::app.settings.channels.create-success'));

        return redirect()->route('admin.channels.index');
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
        $channel = $this->channelRepository->with(['locales', 'currencies'])->findOrFail($id);

        return view('admin::settings.channels.edit', compact('channel'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update($id)
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:channels,code,' . $id, new \BagistoPackages\Shop\Contracts\Validations\Code],
            'name' => 'required',
            'locales' => 'required|array|min:1',
            'inventory_sources' => 'required|array|min:1',
            'default_locale_id' => 'required|in_array:locales.*',
            'currencies' => 'required|array|min:1',
            'base_currency_id' => 'required|in_array:currencies.*',
            'root_category_id' => 'required',
            'logo.*' => 'mimes:bmp,jpeg,jpg,png,webp',
            'favicon.*' => 'mimes:bmp,jpeg,jpg,png,webp',
            'hostname' => 'unique:channels,hostname,' . $id,
        ]);

        $data = request()->all();

        $data['seo']['meta_title'] = $data['seo_title'];
        $data['seo']['meta_description'] = $data['seo_description'];
        $data['seo']['meta_keywords'] = $data['seo_keywords'];

        unset($data['seo_title']);
        unset($data['seo_description']);
        unset($data['seo_keywords']);

        $data['home_seo'] = json_encode($data['seo']);

        Event::dispatch('core.channel.update.before', $id);

        $channel = $this->channelRepository->update($data, $id);

        if ($channel->base_currency->code !== session()->get('currency')) {
            session()->put('currency', $channel->base_currency->code);
        }

        Event::dispatch('core.channel.update.after', $channel);

        session()->flash('success', trans('admin::app.settings.channels.update-success'));

        return redirect()->route('admin.channels.index');
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
        $channel = $this->channelRepository->findOrFail($id);

        if ($channel->code == config('app.channel')) {
            session()->flash('error', trans('admin::app.settings.channels.last-delete-error'));
        } else {
            try {
                Event::dispatch('core.channel.delete.before', $id);

                $this->channelRepository->delete($id);

                Event::dispatch('core.channel.delete.after', $id);

                session()->flash('success', trans('admin::app.settings.channels.delete-success'));

                return response()->json(['message' => true], 200);
            } catch (\Exception $e) {
                // session()->flash('warning', trans($e->getMessage()));
                session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Channel']));
            }
        }

        return response()->json(['message' => false], 400);
    }
}
