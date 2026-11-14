@switch($status)
    @case('active')
        <span class="badge badge-ok">@lang('platform::app.status.active')</span>
        @break
    @case('suspended')
        <span class="badge badge-warn">@lang('platform::app.status.suspended')</span>
        @break
    @default
        <span class="badge badge-muted">@lang('platform::app.status.provisioning')</span>
@endswitch
