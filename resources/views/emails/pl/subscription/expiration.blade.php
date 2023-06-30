@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Twój pakiet premium wkrótce wygaśnie. Za {{ $days }} dni termin ważności Twojego pakietu wygaśnie.
    </p>
    <p>
        Przypominamy, że pakiet premium umożliwia Ci korzystanie z pełnych funkcjonalności naszego serwisu. W związku z tym, zachęcamy Cię do przedłużenia pakietu, aby nie przerwać dostępu do tych usług.
    </p>
    <p>
        Aby przedłużyć pakiet, kliknij poniższy link.
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Przedłuż pakiet
            </a>
        </div>
    </p>
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection