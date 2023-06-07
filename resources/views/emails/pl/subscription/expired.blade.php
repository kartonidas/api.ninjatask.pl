@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Chcielibyśmy poinformować Cię, że Twoja subskrypcja naszej usługi została zakończona. Pragniemy podziękować Ci za korzystanie z naszych usług i za okazane nam zaufanie.
    </p>
    <p>
        W związku z zakończeniem subskrypcji, dostęp do funkcji premium oraz treści został zakończony. Mamy nadzieję, że byłeś/byłaś zadowolony/zadowolona z naszej usługi i że spełniliśmy Twoje oczekiwania podczas trwania subskrypcji.
    </p>
    <p>
        W każdej chwili możesz ponownie wykupić subskrypcję. Aby to zrobić kliknij w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ env("FRONTEND_URL") }}subscription" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Wykup subskrypcję
            </a>
        </div>
    </p>
    <p>
        Pragniemy podkreślić, że cenimy Twoje zaufanie i jesteśmy wdzięczni za Twoje dotychczasowe korzystanie z naszych usług. Jeśli masz jakiekolwiek sugestie lub opinie, które mogą pomóc nam w dalszym udoskonalaniu naszej oferty, prosimy o podzielenie się nimi.
    </p>
    <p>
        Z wyrazami szacunku,
        <br/>
        Zespół ninjaTask
    </p>
@endsection