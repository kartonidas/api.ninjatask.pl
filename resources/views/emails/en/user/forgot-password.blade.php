@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        To set a new password click on the link below to open the password reset page:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Set new password
            </a>
        </div>
    </p>
    
    <p style="font-size: 14px">
        If you did not request a password reset or have any concerns, please contact our customer support team immediately.
    </p>
        
    <p style="font-size: 14px">
        If you encounter any issues with the password reset procedure or require further assistance contact with us.
    </p>
        
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection

