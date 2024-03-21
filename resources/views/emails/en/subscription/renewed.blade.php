@extends("emails.template")

@section("content")
    Thank you for renewing and paying for your subscription.
    <br/>
    The subscription will remain valid until: {{ date("Y-m-d H:i:s", $subscription->end) }}
    
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        You will find the invoice for the purchase in the section <a href="{{ env("FRONTEND_URL") }}invoices" style="text-decoration: none">"Settlement documents"</a>.
    </div>
        
    @include("emails.en.footer")
@endsection