@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    @if($source == "app")
        <div margin-bottom: 10px;>
            <p>
                Thank you for registering on our platform! We are delighted that you have joined our community. 
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
                To complete the registration process, kindly confirm your account by clicking on the link provided below and filling in any missing information.
            </p>
        </div>
            
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-up/confirm/{{ $token->token }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Confirm your registration
            </a>
        </div>
    @endif
    
    <p>
        Please note that the information you provide is essential for the proper functioning of our platform and to deliver personalized and valuable content to you. We assure you that your information will be stored in accordance with our privacy policy and will not be shared with any third parties without your consent.
    </p>
    <p>
        If you have any questions or need assistance in completing your details, please don't hesitate to contact our customer support team. We are here to help and provide any necessary information.
    </p>
    <p>
        Once again, thank you for your trust and for joining our community. We look forward to embarking on this journey together and assure you that we will continuously work towards delivering the best services and experiences to you.
    </p>
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection