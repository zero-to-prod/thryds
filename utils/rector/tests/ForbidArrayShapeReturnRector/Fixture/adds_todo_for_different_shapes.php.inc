<?php

namespace Fixture;

class MultiReturn {
    public function getData(bool $flag): array {
        if ($flag) {
            return [
                'name' => 'John',
                'email' => 'john@example.com',
            ];
        }

        return [
            'name' => 'Jane',
            'age' => 30,
        ];
    }
}

?>
-----
<?php

namespace Fixture;

class MultiReturn {
    // TODO: Replace array return with a typed class
    public function getData(bool $flag): array {
        if ($flag) {
            return [
                'name' => 'John',
                'email' => 'john@example.com',
            ];
        }

        return [
            'name' => 'Jane',
            'age' => 30,
        ];
    }
}

?>
