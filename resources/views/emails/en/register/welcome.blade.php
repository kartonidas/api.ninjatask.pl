@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Thank you for completing your registration in our app! For now on, you can log in and use the full capabilities of the application.
    </p>
        
    <p>
        To log in to the app, please click on the link below:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}{{ $locale }}/sign-in" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Log in.
            </a>
        </div>
    </p>
    
    <p>
        If you have any questions, encounter any difficulties logging in, or need assistance in using our app, our customer support team is ready to help. 
    </p>
    <p>
        Thank you for choosing our application and the trust you have placed in us. We hope that the application will meet your expectations.
    </p>
        
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection