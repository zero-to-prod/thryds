<?php

class TaskPayload {}

class TaskRunner {
    public function run(TaskPayload $payload): void
    {
    }
}

$TaskRunner = new TaskRunner();
$TaskPayload = new TaskPayload();
$TaskRunner->run($TaskPayload);

?>
-----
<?php

class TaskPayload {}

class TaskRunner {
    public function run(TaskPayload $payload): void
    {
    }
}

$TaskRunner = new TaskRunner();
$TaskPayload = new TaskPayload();
$TaskRunner->run(payload: $TaskPayload);

?>
