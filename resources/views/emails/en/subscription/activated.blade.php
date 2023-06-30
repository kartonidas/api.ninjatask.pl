@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Thank you for purchasing the premium package of our application!
    </p>
    <p>
        We are committed to providing you with the highest quality service and continuous improvement of our application.
    </p>
    <p>
        If you have any questions, concerns or suggestions regarding our app, please don't hesitate to contact our customer service team.
    </p>
    <p>
        Best regards,
        <br/>
        ninjaTask team
    </p>
@endsection