@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Użytkownik {{ $user->firstname }} {{ $user->lastname }} zaprosił Cię do naszej aplikacji.
    </p>
        
    <p>
        Aby aktywować swoje konto, proszę kliknąć w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Aktywuj konto
            </a>
        </div>
    </p>
    <p>
        Link przeniesie Cię do formularza, gdzie będziesz mógł/mogła uzupełnić swoje dane, takie jak imię, nazwisko, hasło itp. Upewnij się, że podajesz prawidłowe informacje, abyśmy mogli utworzyć i skonfigurować Twoje konto zgodnie z naszymi standardami.
    </p>
        
    <p>
        Jeśli masz jakiekolwiek pytania lub potrzebujesz pomocy w procesie aktywacji konta, proszę o kontakt. Jesteśmy tutaj, aby Ci pomóc i upewnić się, że jesteś gotowy/gotowa rozpocząć korzystanie z naszej aplikacji.
    </p>
        
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection