@extends("emails.template", ["hide_bottom" => true])

@section("content")
    <div style="margin-bottom: 15px;">
        <div style="margin-top:15px; margin-bottom: 15px">
            Changing the task status:
            <a href="{{ $url }}" style="color: #506fd9; text-decoration: none;">
                <b>{{ $task->name }}</b>
            </a>
            @if($project)
                <div>
                    Place: {{$project->name}}
                </div>
            @endif
            New status: <b>{{ $task->getStatusName($task->uuid) }}</b>
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