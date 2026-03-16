<?php

namespace Fixture;

$emit_error = static function (string $msg, int $code) use ($Blade): void {
    $html = $Blade->make('error', ['msg' => $msg])->render();
    (new SapiEmitter())->emit(new HtmlResponse($html, $code));
    log_something();
    finish();
};

?>
-----
<?php

namespace Fixture;

function emit_error(mixed $Blade, string $msg, int $code): void
{
    $html = $Blade->make('error', ['msg' => $msg])->render();
    (new SapiEmitter())->emit(new HtmlResponse($html, $code));
    log_something();
    finish();
}
$emit_error = (static fn(string $msg, int $code): void => emit_error($Blade, $msg, $code));

?>
