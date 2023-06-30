@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Aby ustawić nowe hasło, kliknij w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Ustaw nowe hasło
            </a>
        </div>
    </p>
    
    <p style="font-size: 14px">
        Jeśli to nie Ty wnioskowałeś o zmianę hasła lub masz jakiekolwiek wątpliwości, prosimy o niezwłoczny kontakt.
    </p>
        
    <p style="font-size: 14px">
        Jeżeli masz jakiekolwiek problemy z procedurą zmiany hasła lub potrzebujesz dodatkowej pomocy, skontaktuj się z nami, a chętnie udzielimy Ci niezbędnej pomocy.
    </p>
        
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection

