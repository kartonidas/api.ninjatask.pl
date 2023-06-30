@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
       Your premium package will expire soon. Your package will expire in {{ $days }} days.
    </p>
    <p>
        We would like to remind you that the premium package allows you to use the full functionalities of our website. Therefore, we encourage you to extend your package so as not to interrupt your access to these services.
    </p>
    <p>
        To renew your premium package, click the link below.
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Renew premium package
            </a>
        </div>
    </p>
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection