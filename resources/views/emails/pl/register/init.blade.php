<div>
    Dziękujemy za rejestrację.
    <br/>
    @if($source == "app")
        Twój kod: {{ $token->code }}.
        <br/>
        Alternatywnie aby dokończyć rejestrację i założyć konto kliknij w poniższy link:
        <br/>
        <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}">
            {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
        </a>
    @else
        Aby dokończyć rejestrację i założyć konto kliknij w poniższy link:
        <br/>
        <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}">
            {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
        </a>
    @endif
</div>