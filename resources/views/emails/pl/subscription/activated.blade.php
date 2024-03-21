@extends("emails.template")

@section("content")
    Dziękujemy za wykupienie i opłacenie subskrypcji.
    <br/>
    Subskrypcja pozostanie ważna do dnia: {{ date("Y-m-d H:i:s", $subscription->end) }}
    
    <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
        Fakturę za zakup znajdziesz w dziale <a href="{{ env("FRONTEND_URL") }}invoices" style="text-decoration: none">"Dokumenty rozliczeniowe"</a>.
    </div>
    
    @include("emails.pl.footer")
@endsection