@extends("emails.template")

@section("content")
    W dniu {{ date("Y-m-d H:i:s", $subscription->end) }} Twoja subskrypcja wygasła.
    
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        W każdej chwili możesz ponownie wykupić subskrypcję. Aby to zrobić, kliknij poniższy link:
        <div style="text-align: left; ">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="text-decoration: none; ">
                {{ env("FRONTEND_URL") }}subscription
            </a>
        </div>
    </div>
        
    @include("emails.pl.footer")
@endsection