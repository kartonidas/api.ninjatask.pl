@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        A new task has been assigned to your account.
    </p>
    
    <p>
        Task name:
        <br/>
        {{ $task->name }}
    </p>
    @if(!empty($task->description))
        <p>
            Task description:
            <br/>
            {{ strip_tags($task->description) }}
        </p>
    @endif
    <p>
        To go to the details of the task, click on the link below:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Go to task
            </a>
        </div>
    </p>
@endsection