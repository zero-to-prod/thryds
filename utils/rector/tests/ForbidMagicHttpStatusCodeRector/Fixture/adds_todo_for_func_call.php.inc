<?php

return response('Not Found', 404);
return response('Created', 201);

?>
-----
<?php

// TODO: Replace 404 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return response('Not Found', 404);
// TODO: Replace 201 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return response('Created', 201);

?>
