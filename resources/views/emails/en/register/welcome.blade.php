@extends("emails.template")

@section("content")
    Thank you for your trust and completing the registration. Your account is now ready to go.
    
    <div style="margin-top: 10px">
        To log in to the application, click the link below:
        <div style="text-align: left; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-in" style="text-decoration: none">
                {{ env("FRONTEND_URL") }}{{ $locale }}/sign-in
            </a>
        </div>
    </div>
    
    @include("emails.en.footer")
@endsection