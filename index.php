<?php
// Uključi ruter
require_once 'router.php';

// Pokreni ruter
$router = new Router();
$router->handleRequest();
