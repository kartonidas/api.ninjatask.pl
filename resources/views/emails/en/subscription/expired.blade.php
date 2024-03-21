@extends("emails.template")

@section("content")
    On {{ date("Y-m-d H:i:s", $subscription->end) }} Your subscription has expired..
    
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        You can re-subscribe at any time. To do this, click the link below:
        <div style="text-align: left; ">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="text-decoration: none; ">
                {{ env("FRONTEND_URL") }}subscription
            </a>
        </div>
    </div>
        
    @include("emails.en.footer")
@endsection