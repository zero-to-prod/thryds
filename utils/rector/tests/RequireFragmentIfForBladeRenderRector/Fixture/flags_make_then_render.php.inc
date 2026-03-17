<?php

$Blade->make(view: 'home')->render();
-----
<?php

// TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page. For htmx partial requests, use ->fragmentIf($request->hasHeader(Header::hx_request), 'body') instead.
$Blade->make(view: 'home')->render();
