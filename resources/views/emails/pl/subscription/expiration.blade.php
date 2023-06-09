@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Chcielibyśmy przypomnieć Ci, że Twój pakiet premium zbliża się do końca. Za {{ $days }} dni termin ważności Twojego pakietu wygaśnie.
    </p>
    <p>
        Przypominamy, że pakiet premium umożliwia Ci korzystanie z pełnych funkcjonalności naszego serwisu i cieszenie się jego korzyściami. W związku z tym, zachęcamy Cię do przedłużenia pakietu, aby nie przerwać Ci dostępu do tych usług.
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
        Pragniemy podkreślić, że cenimy Twoje zaufanie i jesteśmy wdzięczni za Twoje dotychczasowe korzystanie z naszych usług. Jeśli masz jakiekolwiek sugestie lub opinie, które mogą pomóc nam w dalszym udoskonalaniu naszej oferty, prosimy o podzielenie się nimi.
    </p>
    <p>
        Dziękujemy za Twoje wsparcie i mamy nadzieję, że będziemy mogli kontynuować dostarczanie Ci wartościowych usług przez dalsze lata.
    </p>
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection