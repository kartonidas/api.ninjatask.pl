@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        W zadaniu które utworzyłeś został dodany komentarz.
    </p>
    
    <p>
        <b>Nazwa zadania:</b>
        <br/>
        {{ $task->name }}
    </p>
    <p>
        <b>Komentarz:</b>
        <br/>
        {{ strip_tags($comment->comment) }}
    </p>
    <p>
        Aby przejść do szczegółów zadania kliknij w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Zobacz zadanie
            </a>
        </div>
    </p>
@endsection