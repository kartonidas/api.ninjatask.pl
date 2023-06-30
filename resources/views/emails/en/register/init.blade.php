@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    @if($source == "app")
        <div margin-bottom: 10px;>
            <p>
                Thank you for registering with ninjaTask! We are happy that you have joined our application. 
                To complete the registration process, confirm your account by entering the code below in the app:
            </p>
            <p style="font-size:20px;">
                <b>{{ $token->code }}</b>
            </p>
            <p>
                or click on the link provided below and filling in any missing information.
            </p>
        </div>
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Confirm your registration
            </a>
        </div>
    @else
        <div margin-bottom: 10px;>
            <p>
                Thank you for registering on our platform! We are delighted that you have joined our community.
                To complete the registration process, please confirm your account by clicking the link below and fill in the missing data.
            </p>
        </div>
            
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Confirm your registration
            </a>
        </div>
    @endif
    
    <p>
        We remind you that the data you provide is necessary for the proper functioning of the application. We assure you that your data will be stored in accordance with our privacy policy and will not be shared with any third parties without your consent.
    </p>
    <p>
        If you have any questions or need assistance in completing your details, please don't hesitate to contact our customer support team. 
    </p>
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection