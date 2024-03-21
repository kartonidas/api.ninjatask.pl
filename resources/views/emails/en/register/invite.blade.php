@extends("emails.template")

@section("content")
    User {{ $user->firstname }} {{ $user->lastname }} has invited you to create an account in the ninjatask.pl application.
        
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        To create an account, click the link below:
        <div>
            <a href="{{ $url }}" style="text-decoration: none;">
                {{ $url }}
            </a>
        </div>
    </div>
        
    @include("emails.en.footer")
@endsection