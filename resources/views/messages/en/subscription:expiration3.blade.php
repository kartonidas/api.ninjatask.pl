<h5>The premium package will expire soon</h5>
<p>
    On <b>{{ date("Y-m-d H:i:s", $row->end) }}</b> your premium package will expire.<br/>
    To be able to continue to use the full version of the website, extend its validity before the expiry of this period.<br/>
    To do this, you can use the button below:<br/>
</p>
<a href="{{ env("FRONTEND_URL") }}subscription" class="btn btn-primary">Buy a premium package</a>
