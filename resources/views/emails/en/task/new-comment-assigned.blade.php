@extends("emails.template", ["hide_bottom" => true])

@section("content")
    <div style="margin-bottom: 15px;">
        <div style="margin-top:15px; margin-bottom: 15px">
            In tasks:
            <a href="{{ $url }}" style="color: #506fd9; text-decoration: none;">
                <b>{{ $task->name }}</b>
            </a>
            @if($project)
                <div>
                    Place: {{$project->name}}
                </div>
            @endif
        </div>
        A new comment has been added:
        @if(!empty($comment->comment))
            <div style="font-size: 13px margin-top:5px">
                <i>
                    {{ strip_tags($comment->comment) }}
                </i>
            </div>
        @endif
    </div>    
@endsection