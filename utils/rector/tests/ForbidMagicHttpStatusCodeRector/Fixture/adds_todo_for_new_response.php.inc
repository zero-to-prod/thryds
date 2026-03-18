<?php

return new Response('OK', 200);
return new JsonResponse(['error' => 'Unauthorized'], 401);

?>
-----
<?php

// TODO: Replace 200 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return new Response('OK', 200);
// TODO: Replace 401 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return new JsonResponse(['error' => 'Unauthorized'], 401);

?>
