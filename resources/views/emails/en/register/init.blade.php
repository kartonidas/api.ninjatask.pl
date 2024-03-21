@extends("emails.template")

@section("content")
    @if($source == "app")
        Thank you for registering on ninjatask.pl, to complete the process of creating a new account, please enter the following code in the application:
            
        <div style="font-size:20px; margin-top: 15px; margin-bottom: 15px;">
            <b>{{ $token->code }}</b>
        </div>
            
        or clicking the link below:
                
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="text-decoration: none">
                {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
            </a>
        </div>
    @else
        Thank you for registering on ninjatask.pl, to complete the process of creating a new account, click the link below:
            
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="text-decoration: none">
                {{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}
            </a>
        </div>
    @endif
    
    @include("emails.en.footer")
@endsection