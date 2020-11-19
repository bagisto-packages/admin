@component('admin::emails.layouts.master')
    <div style="text-align: center;">
        <a href="{{ config('app.url') }}">
            @include ('admin::emails.layouts.logo')
        </a>
    </div>

    <div style="padding: 30px;">
        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            {{ __('admin::app.mail.update-password.dear', ['name' => $user->name]) }},
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            {{ __('admin::app.mail.update-password.info') }}
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            {{ __('admin::app.mail.update-password.thanks') }}
        </p>
    </div>
@endcomponent
