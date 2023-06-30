@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Użytkownik {{ $user->firstname }} {{ $user->lastname }} zaprosił Cię do naszej aplikacji.
    </p>
        
    <p>
        Aby aktywować konto kliknij w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Aktywuj konto
            </a>
        </div>
    </p>
    <p>
        Link przeniesie Cię do formularza, gdzie będziesz mógł uzupełnić podstawowe dane.
    </p>
        
    <p>
        Jeśli masz jakiekolwiek pytania lub potrzebujesz pomocy w procesie aktywacji konta, proszę o kontakt.
    </p>
        
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection