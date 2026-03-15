<?php

class DispatchResult {}

class RequestDispatcher {
    public function dispatch(): DispatchResult
    {
        return new DispatchResult();
    }
}

$dispatcher = new RequestDispatcher();
$result = $dispatcher->dispatch();
echo $result;

?>
-----
<?php

class DispatchResult {}

class RequestDispatcher {
    public function dispatch(): DispatchResult
    {
        return new DispatchResult();
    }
}

$RequestDispatcher = new RequestDispatcher();
$DispatchResult = $RequestDispatcher->dispatch();
echo $DispatchResult;

?>
