<?php

class LogEntry {}

class LogWriter {
    public function write(LogEntry $entry, string $channel): void
    {
    }
}

$LogWriter = new LogWriter();
$LogEntry = new LogEntry();
$channel = 'main';
$LogWriter->write($LogEntry, $channel);

?>
-----
<?php

class LogEntry {}

class LogWriter {
    public function write(LogEntry $entry, string $channel): void
    {
    }
}

$LogWriter = new LogWriter();
$LogEntry = new LogEntry();
$channel = 'main';
$LogWriter->write(entry: $LogEntry, channel: $channel);

?>
