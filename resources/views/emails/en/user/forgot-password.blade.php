@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        In response to your request to reset your password in our application, we are providing you with instructions on the password reset procedure.
        To set a new password click on the link below to open the password reset page:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Set new password
            </a>
        </div>
    </p>
    
    <p>
        If you did not request a password reset or have any concerns, please contact our customer support team immediately. We will take the necessary steps to protect your account.
    </p>
        
    <p>
        If you encounter any issues with the password reset procedure or require further assistance, our customer support team is ready to assist you. Please reach out to us, and we will be happy to provide you with the necessary help.
    </p>
        
    <p>
        Thank you for using our application and placing your trust in us. We prioritize the security of your account and aim to deliver the best experiences to you.
    </p>
        
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection

