@extends("emails.template")

@section("content")
    To change the password for your ninjatask.pl account, click the link below:
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        <a href="{{ $url }}" style="text-decoration: none">
            {{ $url }}
        </a>
    </div>
    If you do not intend to change your password at this time, please ignore this message. Your current password will remain unchanged.
    
    @include("emails.en.footer")
@endsection

