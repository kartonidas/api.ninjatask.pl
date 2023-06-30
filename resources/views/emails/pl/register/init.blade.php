@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    @if($source == "app")
        <div margin-bottom: 10px;>
            <p>
                Dziękujemy za rejestrację w serwisie ninjaTask! Jesteśmy szczęśliwi, że dołączyłeś do naszej aplikacji.
                Aby dokończyć proces rejestracji, prosimy o potwierdzenie swojego konta poprzez podanie poniższego kodu w aplikacji:
            </p>
            <p style="font-size:20px;">
                <b>{{ $token->code }}</b>
            </p>
            <p>
                albo kliknięcie w poniższy link oraz uzupełnienie brakujących danych.
            </p>
        </div>
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Potwierdź rejestrację
            </a>
        </div>
    @else
        <div margin-bottom: 10px;>
            <p>
                Dziękujemy za rejestrację w serwisie ninjaTask! Jesteśmy szczęśliwi, że dołączyłeś do naszej aplikacji.
                Aby dokończyć proces rejestracji, prosimy o potwierdzenie swojego konta poprzez kliknięcie w poniższy link oraz uzupełnienie brakujących danych.
            </p>
        </div>
            
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Potwierdź rejestrację
            </a>
        </div>
    @endif
    
    <p>
        Przypominamy, że dane, które podasz, są niezbędne do prawidłowego funkcjonowania aplikacji. Zapewniamy Ci, że Twoje dane będą przechowywane zgodnie z naszą polityką prywatności i nie będą udostępniane osobom trzecim bez Twojej zgody.
    </p>
    <p>
        Jeśli masz jakiekolwiek pytania lub potrzebujesz pomocy w uzupełnieniu danych, nie wahaj się skontaktować z naszym zespołem obsługi klienta.
    </p>
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection