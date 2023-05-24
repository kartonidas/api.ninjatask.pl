<div>
    Thank you for your registration.
    <br/>
    @if($source == "app")
        Your confirmation code: {{ $token->code }}.
        <br/>
        Alternative, to complete your registration and create an account, click on the link below:
        <br/>
        <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}">
            {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
        </a>
    @else
        To complete your registration and create an account, click on the link below:
        <br/>
        <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}">
            {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
        </a>
    @endif
</div>