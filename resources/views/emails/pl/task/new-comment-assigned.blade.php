@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Nowy komentarz w zadaniu: 
        <a href="{{ $url }}" style="color: #506fd9; text-decoration: none;">
            <b>{{ $task->name }}</b>
        </a>
        [<b>{{ $task->getStatusName($task->uuid) }}</b>]
    </p>
    <p style="font-size: 13px">
        <i>
            {{ strip_tags($comment->comment) }}
        </i>
    </p>
@endsection