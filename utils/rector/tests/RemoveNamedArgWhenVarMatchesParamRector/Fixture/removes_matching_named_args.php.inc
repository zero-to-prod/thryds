<?php

WebRoutes::register(Router: $Router, Blade: $Blade);
doSomething(request: $request);
new HtmlResponse(html: $html);

?>
-----
<?php

WebRoutes::register($Router, $Blade);
doSomething($request);
new HtmlResponse($html);

?>
