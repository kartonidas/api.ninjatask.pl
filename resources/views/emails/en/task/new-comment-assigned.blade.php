@extends("emails.template")

@section("title")
    {{ $title }}
@endsection

@section("content")
    <p>
        A comment has been added to the task you are assigned to.
    </p>
    
    <p>
        <b>Task name:</b>
        <br/>
        {{ $task->name }}
    </p>
    <p>
        <b>Comment:</b>
        <br/>
        {{ strip_tags($comment->comment) }}
    </p>
    <p>
        To go to the details of the task, click on the link below:
        <div style="text-align: left; margin-top: 10px; margin-bottom: 10px">
            <a href="{{ $url }}" style="display:inline-block; background-color: #506fd9; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                Go to task
            </a>
        </div>
    </p>
@endsection