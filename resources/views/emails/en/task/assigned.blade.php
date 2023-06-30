@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        A new task has been added to your account:
        <br/>
        <a href="{{ $url }}" style="color: #506fd9; text-decoration: none;">
            <b>{{ $task->name }}</b>
        </a>
    </p>
    @if(!empty($task->description))
        <p style="font-size: 13px">
            <i>
                {{ strip_tags($task->description) }}
            </i>
        </p>
    @endif
@endsection