@extends("emails.template")

@section("content")
    Użytkownik {{ $user->firstname }} {{ $user->lastname }} zaprosił Cię do założenia konta w aplikacji ninjatask.pl.
        
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        Aby założyć konto, kliknij poniższy link:
        <div>
            <a href="{{ $url }}" style="text-decoration: none;">
                {{ $url }}
            </a>
        </div>
    </div>
        
    @include("emails.pl.footer")
@endsection