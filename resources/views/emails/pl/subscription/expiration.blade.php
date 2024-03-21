@extends("emails.template")

@section("content")
    Twoja subskrypcja wygaśnie za {{ $days }} {{ \App\Libraries\Helper::plurals($days, "dzień", "dni", "dni") }} (ważność subskrypcji upływa w dniu: {{ date("Y-m-d H:i:s", $subscription->end) }}).
    
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        Aby przedłużyć subskrypcję, kliknij poniższy link:
        <div style="text-align: left; ">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="text-decoration: none;">
                {{ env("FRONTEND_URL") }}subscription
            </a>
        </div>
    </div>
        
    @include("emails.pl.footer")
@endsection