@extends("emails.template")

@section("content")
    Dziękujemy za okazane zaufanie i dokończenie rejestracji. Twoje konto jest już gotowe do pracy.
    
    <div style="margin-top: 10px">
        Aby zalogować się do aplikacji, kliknij poniższy link:
        <div style="text-align: left; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-in" style="text-decoration: none">
                {{ env("FRONTEND_URL") }}{{ $locale }}/sign-in
            </a>
        </div>
    </div>
    
    @include("emails.pl.footer")
@endsection