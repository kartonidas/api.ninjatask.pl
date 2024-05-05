<style>
    div {
        font-family: verdana;
        font-size: 12px;
    }
    
    .ql-align-center {
        text-align:center;
    }
    
    .ql-align-right {
        text-align:right;
    }
    
    .ql-align-justify {
        text-align:justify;
    }
    
    p {
        margin-bottom: 5px;
        margin-top: 5px;
    }
</style>
    
<div>
    {!! $content !!}
</div>
    
@php
    $signature = $document->getSignature();
@endphp
@if($signature)
    <div style="text-align:right; margin-top: 40px;">
        <img src="{{ $signature }}" style="max-width:250px; max-height:125px">
    </div>
@endif