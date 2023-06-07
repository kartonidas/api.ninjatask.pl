@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        W związku z Twoim wnioskiem o zmianę hasła w naszej aplikacji, przesyłamy Ci instrukcje dotyczące procedury zmiany hasła.
        Aby ustawić nowe hasło, kliknij w poniższy link, aby zainicjować zmianę hasła:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Ustaw nowe hasło
            </a>
        </div>
    </p>
    
    <p>
        Jeśli to nie Ty wnioskowałeś/wnioskowałaś o zmianę hasła lub masz jakiekolwiek wątpliwości, prosimy o niezwłoczny kontakt z naszym zespołem obsługi klienta. Będziemy podejmować odpowiednie kroki w celu ochrony Twojego konta.
    </p>
        
    <p>
        Jeżeli masz jakiekolwiek problemy z procedurą zmiany hasła lub potrzebujesz dodatkowej pomocy, nasz zespół obsługi klienta jest gotowy, aby Cię wesprzeć. Skontaktuj się z nami, a chętnie udzielimy Ci niezbędnej pomocy.
    </p>
        
    <p>
        Dziękujemy za korzystanie z naszej aplikacji i zaufanie, jakim nas obdarzyłeś/obdarzyłaś. Zależy nam na zapewnieniu bezpieczeństwa Twojego konta i dostarczaniu Ci najlepszych doświadczeń.
    </p>
        
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection

