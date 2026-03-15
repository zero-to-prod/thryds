<?php

class EventBus {}

class AppContainer {
    public function getEventBus(): EventBus
    {
        return new EventBus();
    }
}

$AppContainer = new AppContainer();
$EventBus = $AppContainer->getEventBus();
echo $EventBus;

?>
