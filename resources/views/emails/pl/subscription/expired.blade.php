@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Twój pakiet premium wygasł. Pragniemy podziękować Ci za korzystanie z naszej aplikacji i za okazane nam zaufanie.
    </p>
    <p>
        W związku z wygaśnięciem pakietu, dostęp do funkcji premium został zakończony.
    </p>
    <p>
        W każdej chwili możesz ponownie wykupić pakiet premium. Aby to zrobić kliknij w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Wykup pakiet premium
            </a>
        </div>
    </p>
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection