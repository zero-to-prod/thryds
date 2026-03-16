<?php

$request_id = RequestId::init($server_request_interface);
$response = handle($request_id);

?>
-----
<?php

$response = handle(RequestId::init($server_request_interface));

?>
