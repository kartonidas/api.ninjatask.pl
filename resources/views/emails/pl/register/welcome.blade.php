@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Serdecznie dziękujemy za dokończenie rejestracji w naszej aplikacji! Jesteśmy bardzo zadowoleni, że jesteś częścią naszej społeczności. Teraz możesz zalogować się i rozpocząć korzystanie z pełnych możliwości naszej aplikacji.
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
        Jeśli masz jakiekolwiek pytania, napotkasz trudności lub potrzebujesz pomocy w korzystaniu z naszej aplikacji, nasz zespół wsparcia klienta jest dostępny, aby Ci pomóc. Nie wahaj się skontaktować z nami w razie jakichkolwiek wątpliwości.
    </p>
    <p>
        Dziękujemy Ci za wybór naszej aplikacji i zaufanie, jakim nas obdarzyłeś/obdarzyłaś. Mamy nadzieję, że aplikacja spełni Twoje oczekiwania i przyczyni się do ułatwienia Twojego życia. Cieszymy się na długotrwałą współpracę i jesteśmy gotowi dostarczyć Ci najlepsze możliwe doświadczenia.
    </p>
        
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection