<?php

$Blade->make(view: 'error', data: []);
$Blade->make(view: 'components.button', data: []);
$Blade->make(view: 'layouts.app', data: ['user' => $user]);

?>
-----
<?php

$Blade->make(view: \App\View::error, data: []);
$Blade->make(view: \App\View::components_button, data: []);
$Blade->make(view: \App\View::layouts_app, data: ['user' => $user]);

?>
