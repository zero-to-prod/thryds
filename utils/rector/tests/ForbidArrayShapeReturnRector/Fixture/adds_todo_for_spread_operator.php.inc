<?php

namespace Fixture;

class SpreadService {
    public function getData(): array {
        $extra = ['c' => 3];
        return [
            'a' => 1,
            'b' => 2,
            ...$extra,
        ];
    }
}

?>
-----
<?php

namespace Fixture;

class SpreadService {
    // TODO: Replace array return with a typed class
    public function getData(): array {
        $extra = ['c' => 3];
        return [
            'a' => 1,
            'b' => 2,
            ...$extra,
        ];
    }
}

?>
