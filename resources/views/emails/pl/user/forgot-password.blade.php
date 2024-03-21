@extends("emails.template")

@section("title")
    Zmiana hasła
@endsection

@section("content")
    Aby zmienić hasło do konta w serwisie ninjatask.pl, kliknij poniższy link:
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        <a href="{{ $url }}" style="text-decoration: none">
            {{ $url }}
        </a>
    </div>
    Jeśli nie masz zamiaru zmieniać hasła w tym momencie, zignoruj tę wiadomość. Twoje obecne hasło pozostanie bez zmian.
    
    @include("emails.pl.footer")
@endsection

