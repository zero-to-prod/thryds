<?php

$Blade->make(view: View::error, data: [
    'status_code' => $HttpException->getStatusCode(),
    'message' => $HttpException->getMessage(),
])->render();

?>
-----
<?php

$Blade->make(view: View::error, data: [class_basename(\Fixture\ViewModels\ErrorViewModel::class) => \Fixture\ViewModels\ErrorViewModel::from([\Fixture\ViewModels\ErrorViewModel::status_code => $HttpException->getStatusCode(), \Fixture\ViewModels\ErrorViewModel::message => $HttpException->getMessage()])])->render();

?>
