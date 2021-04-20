## Creating a command
The current implementation simply uses the already existing controller structure. Nothing extra needed to do.

## Running a command
To run a command, simply use: `php index.php run <controller> <action> <param1> ...` 

Be sure to use `chdir(realpath(__DIR__))` in your index.php if you are running commands from a different working directory, e.g. as a cronjob.