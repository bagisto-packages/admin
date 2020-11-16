<?php

namespace BagistoPackages\Admin\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel as Excel;
use BagistoPackages\Admin\Exports\DataGridExport;

class ExportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * function for export datagrid
     *
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        $criteria = request()->all();

        $format = $criteria['format'];

        $gridName = explode('\\', $criteria['gridName']);

        $path = '\BagistoPackages\Admin\DataGrids' . '\\' . last($gridName);

        $gridInstance = new $path;

        $records = $gridInstance->export();

        if (!count($records)) {
            session()->flash('warning', trans('admin::app.export.no-records'));

            return redirect()->back();
        }

        if ($format == 'csv') {
            return Excel::download(new DataGridExport($records), last($gridName) . '.csv');
        }

        if ($format == 'xls') {
            return Excel::download(new DataGridExport($records), last($gridName) . '.xlsx');
        }

        session()->flash('warning', trans('admin::app.export.illegal-format'));

        return redirect()->back();
    }
}
