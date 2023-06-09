@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        We would like to inform you that your premium package has been expired. We want to express our gratitude for using our services and for the trust you have placed in us.
    </p>
    <p>
        As a result of the package expired, access to premium features and content has been discontinued. We hope that you were satisfied with our service and that we met your expectations during the package period.
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
        We value your trust and are grateful for your continued use of our services. If you have any suggestions or feedback that can help us further enhance our offerings, please feel free to share them with us.
    </p>
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection