@extends("emails.template")

@section("content")
    Your subscription will expire in {{ $days }} {{ \App\Libraries\Helper::plurals($days, "day", "days", "days") }} (subscription expires on: {{ date("Y-m-d H:i:s", $subscription->end) }}).
    
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        To renew your subscription, click the link below:
        <div style="text-align: left; ">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="text-decoration: none;">
                {{ env("FRONTEND_URL") }}subscription
            </a>
        </div>
    </div>
        
    @include("emails.en.footer")
@endsection