<div class="tabbing-block">
        <ul class="tab-heading-block">
            <li class="{{ (request()->is('access-permissions/roles*')) ? 'active' : '' }}">
                <a href="{{ route('access-permissions.roles') }}" data-tab="praxis" title="{{ __('lang.roles') }}">{{ __('lang.roles') }}</a>
            </li>
            <li class="{{ (request()->is('access-permissions/user-rights*')) ? 'active' : '' }}">
                <a href="{{ route('access-permissions.user-rights') }}" data-tab="mitarbeiter" title="{{ __('lang.user-rights') }}">{{ __('lang.user-rights') }}</a>
            </li>
        </ul>
    <div class="content-wrapper-block fixed-content-block content-scroll-only">
        <div class="inner-content-block">
            <div class="tab-content-block">
                @yield('slot')
            </div>
        </div>
    </div>
</div>