@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        Zmiana statusu zadania: 
        <a href="{{ $url }}" style="color: #506fd9; text-decoration: none;">
            <b>{{ $task->name }}</b>
        </a>
        [<b>{{ $task->getStatusName($task->uuid) }}</b>]
    </p>
    @if(!empty($task->description))
        <p style="font-size: 13px">
            <i>
                {{ strip_tags($task->description) }}
            </i>
        </p>
    @endif
@endsection