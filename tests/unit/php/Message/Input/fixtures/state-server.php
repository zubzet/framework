<?php

    // Served by PHP's built-in web server for StateHttpTest. Answers every request
    // with PHP's own view of it next to what State::fromRequest() made of it.

    use ZubZet\Framework\Message\Input\State;

    // The test that starts the server names the folder of the framework. The state needs
    // nothing else, and without an autoloader no other code runs in here
    require getenv("FRAMEWORK_ROOT") . "/src/Message/Input/State.php";

    $globals = [
        "GET" => $_GET,
        "POST" => $_POST,
        "COOKIE" => $_COOKIE,
        "FILES" => $_FILES,
        "REQUEST" => $_REQUEST,
        "SERVER" => $_SERVER,
    ];

    $state = State::fromRequest();

    // The same request again, built from its own uri
    $rebuilt = State::fromOverwrite($state)->withUrl($_SERVER["REQUEST_URI"]);

    $export = fn(State $state) => [
        "GET" => $state->GET,
        "POST" => $state->POST,
        "COOKIE" => $state->COOKIE,
        "FILES" => $state->FILES,
        "REQUEST" => $state->REQUEST,
        "SERVER" => $state->SERVER,
        "body" => $state->body,
    ];

    header("Content-Type: application/json");
    echo json_encode([
        "globals" => $globals,
        "state" => $export($state),
        "rebuilt" => $export($rebuilt),
        "postAfterwards" => $_POST,
    ], JSON_THROW_ON_ERROR);

?>
