<?php

namespace Fixture;

class DynamicService {
    public function build(bool $detailed): array {
        $base = ['status' => 'ok'];
        if ($detailed) {
            $base['debug'] = 'info';
        }
        return $base;
    }
}

?>
-----
<?php

namespace Fixture;

class DynamicService {
    // TODO: Replace array return with a typed class
    public function build(bool $detailed): array {
        $base = ['status' => 'ok'];
        if ($detailed) {
            $base['debug'] = 'info';
        }
        return $base;
    }
}

?>
