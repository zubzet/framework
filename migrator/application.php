#!/usr/bin/env php
<?php
    require __DIR__.'/vendor/autoload.php';

    use Symfony\Component\Console\Application;
    use ZubZet\Tooling\Upgrade;

    $application = new Application("ZubZet Tooling");

    $application->add(new Upgrade());

    $application->run();

?>