@extends("emails.template", ["hide_bottom" => true])

@section("content")
    <div style="margin-bottom: 15px;">
        Nowe zadanie do wykonania:
        
        <div style="margin-top:15px; margin-bottom: 15px">
            <a href="{{ $url }}" style="color: #506fd9; text-decoration: none;">
                <b>{{ $task->name }}</b>
            </a>
            @if($project)
                <div>
                    Miejsce: {{$project->name}}
                </div>
            @endif
        </div>
            
        @if(!empty($task->description))
            <div style="font-size: 13px margin-top:5px">
                <i>
                    {{ strip_tags($task->description) }}
                </i>
            </div>
        @endif
    </div>
@endsection