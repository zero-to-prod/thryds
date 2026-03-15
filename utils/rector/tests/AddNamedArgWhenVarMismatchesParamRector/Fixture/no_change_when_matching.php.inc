<?php

class JobQueue {}

class Scheduler {
    public function schedule(JobQueue $queue): void
    {
    }
}

$Scheduler = new Scheduler();
$queue = new JobQueue();
$Scheduler->schedule($queue);

?>
