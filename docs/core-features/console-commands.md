### Console Commands

ZubZet provides a built-in console interface that allows you to manage and interact with the framework directly from the command line.
Console commands enable automation, debugging, and execution of application logic outside the HTTP context.

### How to Run a Command

To execute a console command inside the Docker container, first access the container shell:

```bash
npm run shell
```

Then navigate to the root directory of your project (where the `index.php` file is located) and run:

```bash
php index.php {command}
```

### Available Commands

### completion

Dumps the shell completion script for the ZubZet console.
This improves command-line usability by enabling tab completion.

### help

Displays help information for a specific command or for the console in general.

### list

Lists all available console commands.

### run

Executes a controller action directly from the console environment.
This is useful for running application logic, maintenance tasks, or background operations without an HTTP request.