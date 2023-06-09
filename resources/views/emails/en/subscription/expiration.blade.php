@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        We would like to remind you that your premium package its expiration. In {{ $days }} days, your premium package term will come to an end.
    </p>
    <p>
        We want to emphasize that your premium package provides you with access to the full range of features and benefits offered by our service. Therefore, we encourage you to renew your subscription to ensure uninterrupted access to these services.
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
        We value your trust and are grateful for your continued use of our services. If you have any suggestions or feedback that can help us further enhance our offerings, please feel free to share them with us.
    </p>
    <p>
        Thank you for your support, and we hope to continue providing you with valuable services for years to come.
    </p>
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection