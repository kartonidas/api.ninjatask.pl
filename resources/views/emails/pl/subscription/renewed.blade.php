@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Dziękujemy za odnowienie pakietu premium naszej aplikacji!
    </p>
    <p>
        Zobowiązujemy się do zapewnienia Ci najwyższej jakości obsługi i stałego doskonalenia naszej aplikacji.
    </p>
    <p>
        Jeśli masz jakiekolwiek pytania, wątpliwości lub sugestie dotyczące naszej aplikacji, nie wahaj się skontaktować z naszym zespołem obsługi klienta.
    </p>
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection