@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Dziękujemy za dokończenie rejestracji w naszej aplikacji! Od teraz możesz się logować i korzystać z pełnych możliwości aplikacji.
    </p>
        
    <p>
        Aby zalogować się do aplikacji, prosimy kliknąć w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-in" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Zaloguj się.
            </a>
        </div>
    </p>
    
    <p>
        Jeśli masz jakiekolwiek pytania, napotkasz trudności lub potrzebujesz pomocy w korzystaniu z naszej aplikacji, nasz zespół wsparcia klienta jest dostępny, aby Ci pomóc.
    </p>
    <p>
        Dziękujemy Ci za wybór naszej aplikacji i zaufanie, jakim nas obdarzyłeś. Mamy nadzieję, że aplikacja spełni Twoje oczekiwania.
    </p>
        
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection