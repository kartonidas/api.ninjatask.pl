@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Your premium package has expired. We would like to thank you for using our application and for trusting us.
    </p>
    <p>
        Due to the expiration of the package, access to premium features has ended.
    </p>
    <p>
        You can buy premium package at any time. To do this, click on the link below:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Purchase premium package
            </a>
        </div>
    </p>
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection