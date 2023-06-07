@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Thank you for completing your registration in our app! We are thrilled to have you as part of our community. You can now log in and start enjoying the full capabilities of our application.
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
        If you have any questions, encounter any difficulties logging in, or need assistance in using our app, our customer support team is ready to help. Feel free to reach out to us with any inquiries.
    </p>
    <p>
        We appreciate your choice in using our app and the trust you have placed in us. We hope that the app meets your expectations and provides you with exceptional experiences. We look forward to a long-lasting collaboration and are committed to delivering the best services possible.
    </p>
        
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection