<?php

$Blade->make(view: View::error, data: [
    ErrorViewModel::status_code => $HttpException->getStatusCode(),
    ErrorViewModel::message => $HttpException->getMessage(),
])->render();

?>
