@component('admin::emails.layouts.master')
    <div>
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}">
                @include ('admin::emails.layouts.logo')
            </a>
        </div>

        <div style="font-size:16px; color:#242424; font-weight:600; margin-top: 60px; margin-bottom: 15px">
            {!! __('admin::app.mail.customer.verification.heading') !!}
        </div>

        <div>
            {!! __('admin::app.mail.customer.verification.summary') !!}
        </div>

        <div style="margin-top: 40px; text-align: center">
            <a href="{{ route('shop.customer.verify', $data['token']) }}" style="font-size: 16px;
            color: #FFFFFF; text-align: center; background: #0031F0; padding: 10px 100px;text-decoration: none;">
                {!! __('admin::app.mail.customer.verification.verify') !!}
            </a>
        </div>
    </div>
@endcomponent