@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        User {{ $user->firstname }} {{ $user->lastname }} invites you to use our app.
    </p>
        
    <p>
        To activate your account, please click on the link below:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Activate account
            </a>
        </div>
    </p>
    <p>
        The link will direct you to a form where you can fill in your data.
    </p>
        
    <p>
        If you have any questions or need assistance during the account activation process, please don't hesitate to reach out to us.
    </p>
        
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection