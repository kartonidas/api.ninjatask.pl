@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Do Twojego konta zostało przypisane nowe zadanie.
    </p>
    
    <p>
        Nazwa zadania:
        <br/>
        {{ $task->name }}
    </p>
    @if(!empty($task->description))
        <p>
            Opis zadania:
            <br/>
            {{ strip_tags($task->description) }}
        </p>
    @endif
    <p>
        Aby przejść do szczegółów zadania kliknij w poniższy link:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Zobacz zadanie
            </a>
        </div>
    </p>
@endsection