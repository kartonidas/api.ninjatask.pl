@extends("emails.template")

@section("content")
    @if($source == "app")
        Dziękujemy za rejestrację w serwisie ninjatask.pl, aby dokończyć proces zakładania nowego konta, prosimy o podanie poniższego kodu w aplikacji:
            
        <div style="font-size:20px; margin-top: 15px; margin-bottom: 15px;">
            <b>{{ $token->code }}</b>
        </div>
            
        lub kliknięcie poniższego linku:
                
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="text-decoration: none">
                {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
            </a>
        </div>
    @else
        Dziękujemy za rejestrację w serwisie ninjatask.pl, aby dokończyć proces zakładania nowego konta, kliknij poniższy link:
            
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="text-decoration: none">
                {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
            </a>
        </div>
    @endif
    
    @include("emails.pl.footer")
@endsection