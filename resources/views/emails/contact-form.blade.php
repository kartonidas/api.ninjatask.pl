@extends("emails.template")

@section("content")
    <table style="border-collapse: collapse; width: 100%">
        <tr>
            <td style="width: 140px; padding: 4px; vertical-align: top; font-weight: bold;">Imię i nazwisko:</td>
            <td style="padding: 4px; vertical-align: top;">{{ $data["name"] }} (ID: {{ $user_id }})</td>
        </tr>
        <tr>
            <td style="width: 140px; padding: 4px; vertical-align: top; font-weight: bold;">Adres e-mail:</td>
            <td style="padding: 4px; vertical-align: top;">{{ $data["email"] }}</td>
        </tr>
        <tr>
            <td style="width: 140px; padding: 4px; vertical-align: top; font-weight: bold;">Wiadomość:</td>
            <td style="padding: 4px; vertical-align: top;">{!! nl2br($data["message"]) !!}</td>
        </tr>
    </table>
@endsection