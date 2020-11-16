@extends('admin::layouts.content')

@section('page_title')
    {{ __('admin::app.catalog.categories.title') }}
@stop

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h1>{{ __('admin::app.catalog.categories.title') }}</h1>
            </div>

            <div class="page-action">
                <a href="{{ route('admin.catalog.categories.create') }}" class="btn btn-lg btn-primary">
                    {{ __('admin::app.catalog.categories.add-title') }}
                </a>
            </div>
        </div>

        {!! view_render_event('bagisto.admin.catalog.categories.list.before') !!}

        <div class="page-content">
            {!! app('BagistoPackages\Admin\DataGrids\CategoryDataGrid')->render() !!}
        </div>

        {!! view_render_event('bagisto.admin.catalog.categories.list.after') !!}
    </div>
@stop

@push('scripts')
    <script>
        let deleteFunction = function (e, type) {
            let indexes;
            if (type === 'delete') {
                indexes = $(e.target).parent().attr('id');
            } else {
                $("input[type='checkbox']").attr('disabled', true);

                let formData = {};
                $.each($('form').serializeArray(), function (i, field) {
                    formData[field.name] = field.value;
                });

                indexes = formData.indexes;
            }

            if (indexes) {
                $.ajax({
                    type: 'POST',
                    url: '{{route("admin.catalog.categories.product.count")}}',
                    data: {
                        _token: '{{csrf_token()}}',
                        indexes: indexes
                    },
                    success: function (data) {
                        let message;
                        $("input[type='checkbox']").attr('disabled', false);
                        if (data.product_count > 0) {
                            message = "{{trans('ui::app.datagrid.massaction.delete-category-product')}}";
                            if (type === 'delete') {
                                doAction(e, message);
                            } else {
                                $('form').attr('onsubmit', 'return confirm("' + message + '")');
                            }
                        } else {
                            message = "{{ __('ui::app.datagrid.click_on_action') }}";
                            if (type === 'delete') {
                                doAction(e, message);
                            } else {
                                $('form').attr('onsubmit', 'return confirm("' + message + '")');
                            }
                        }
                    }
                });
            } else {
                $("input[type='checkbox']").attr('disabled', false);
            }
        };

        $(document).ready(function () {
            $("input[type='checkbox']").change(deleteFunction);
        });
    </script>
@endpush
