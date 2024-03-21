@extends("emails.template", ["hide_bottom" => true])

@section("content")
    <div style="margin-bottom: 15px;">
        <div style="margin-top:15px; margin-bottom: 15px">
            W zadania: 
            <a href="{{ $url }}" style="color: #506fd9; text-decoration: none;">
                <b>{{ $task->name }}</b>
            </a>
            @if($project)
                <div>
                    Miejsce: {{$project->name}}
                </div>
            @endif
        </div>
        Został dodany nowy komentarz:
        @if(!empty($comment->comment))
            <div style="font-size: 13px margin-top:5px">
                <i>
                    {{ strip_tags($comment->comment) }}
                </i>
            </div>
        @endif
    </div>    
@endsection