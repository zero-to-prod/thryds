<?php

namespace Fixture;

class Logger
{
    public function setup(): void
    {
        $log_request = function (string $method, string $path, int $status): void {
            $line = sprintf('%s %s %d', $method, $path, $status);
            file_put_contents('/tmp/log', $line, FILE_APPEND);
            echo $line;
            flush();
        };
    }
}

?>
-----
<?php

namespace Fixture;

class Logger
{
    public function setup(): void
    {
        $log_request = (fn(string $method, string $path, int $status): void => $this->logRequest($method, $path, $status));
    }
    private function logRequest(string $method, string $path, int $status): void
    {
        $line = sprintf('%s %s %d', $method, $path, $status);
        file_put_contents('/tmp/log', $line, FILE_APPEND);
        echo $line;
        flush();
    }
}

?>
