<?php

return response('Not Found', HttpStatus::NOT_FOUND);
return new Response('OK', Status::OK);
return $response->withStatus(HttpCode::SERVICE_UNAVAILABLE);
