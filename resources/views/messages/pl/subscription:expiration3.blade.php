<h5>Pakiet premium niebawem wygaśnie</h5>
<p>
    W dniu <b>{{ date("Y-m-d H:i:s", $row->end) }}</b> Twój pakiet premium wygaśnie.<br/>
    Aby móc nadal korzystać z pełnej wersji serwisu przedłuż jego waśność przed upływem tego terminu.<br/>
    Aby to zrobić możesz skorzystać z poniższego przycisku:<br/>
</p>
<a href="{{ env("FRONTEND_URL") }}subscription" class="btn btn-primary">Wykup pakiet premium</a>
