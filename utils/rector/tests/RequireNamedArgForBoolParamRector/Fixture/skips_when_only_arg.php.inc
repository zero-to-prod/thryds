<?php

class Mailer {
    public function send(bool $async = false): void {}
}

$mailer = new Mailer();
$mailer->send(true);
