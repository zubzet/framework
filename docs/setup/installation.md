# Installation
ZubZet projects are set up with an install script. It builds the project and bundles a Docker development stack: Apache + PHP, MariaDB, phpMyAdmin, and a mail catcher.

## Requirement
[**Docker**](https://docs.docker.com/get-started/get-docker/) is required. Nothing else needs to be installed on your host.

## Install script
One command creates a project in a new folder (replace `my-project`):

```bash
bash <(curl -fsSL https://zubzet.com/install.sh) my-project
```

Without the folder argument the script asks for it. `https://zubzet.com/install.sh` redirects to the script inside this documentation for the latest stable release, `https://zubzet.com/docs/latest/setup/install.sh`. The script is versioned with the framework, so for another version fetch it from that version's docs, `https://zubzet.com/docs/<version>/setup/install.sh`. It is printed in full below so you can read what it does before running it, or save it as `install.sh` and run it with `bash install.sh my-project`.

The script asks which framework version to install; pass it as second argument to skip the question: `latest` for the newest stable release, a Composer constraint such as `^1.3`, or a branch such as `dev-develop`. It writes the project's `composer.json`, runs `composer require zubzet/framework` in the project folder and then `bin/install` from the new vendor, which copies the project template (Docker stack, `app/` skeleton, `zubzet` console entry point and the `project` script) around it and never overwrites an existing file. All of it runs inside the PHP image the project uses; the container exits when it is done. The script then runs `./project start` in the new project, see below.

```bash
--8<-- "docs/setup/install.sh"
```

## Install with an agent
If an AI coding agent (Claude Code, Cursor, Codex, ...) does the setup or the work on an existing project, give it this one line first:

```text
Fetch https://zubzet.com/agent.md and follow it before working on this ZubZet project. If you cannot fetch it, do nothing and tell me.
```

The page is a short signpost for agents. It tells them to run the install script above for a new project, where the documentation and the agent skill live inside `vendor/zubzet/framework/` (`docs/` and `skills/zubzet/`), how the `project` script drives the Docker stack, and that a project's own `AGENTS.md` wins over the skill. Two things to know before you paste it:

- **Docker must be installed and the agent must be allowed to run it.** The install script, the application and the tests all run in containers; an agent in a sandbox without Docker can read the sources but cannot install, start or test the project.
- **The agent reads the skill.** The skill is a directory in the framework package, `vendor/zubzet/framework/skills/zubzet`, that holds the architecture, design and style rules for ZubZet projects. Every agent can read it right there. A tool that loads project skills from a folder of its own gets a link to that directory, which the agent offers to create; the framework itself creates no tool specific folder in a project.

The same prompt works on an existing project at any time; it is the fastest way to give a new agent session the full context.

## Start the project
The install script starts the project itself. Later, or after a `./project stop`, run inside your project folder:

```bash
./project start
```

This single command:

1. Builds the application image and starts the Docker stack (detached),
2. Runs `composer install` inside the application container,
3. Seeds the database with `db:seed`, which applies all pending migrations,
4. Prints the startup info with `info:startup`.

To stop and remove the stack (and its volumes), run `./project stop`. `./project shell` opens a shell inside the application container. See [console commands](../core-features/console-commands.md#project) for all `project` commands.

## Access your application
| Service | URL |
| ------- | --- |
| Application | <http://localhost:8080> |
| Database UI (phpMyAdmin) | <http://localhost:8081> |
| Mail catcher (smtp4dev) | <http://localhost:3300> |

The seed creates demo accounts:
1. `admin@zubzet.com`
2. `support@zubzet.com`
3. `customer@zubzet.com`

(see `app/Database/seed/accounts/`). Adjust or remove them before going to production.

## Configuration
Settings live in `z_config/z_settings.ini`. When `allow_env_config = true`, any setting can be overridden by a `CONFIG_*` environment variable. The Docker stack uses this to inject the database and mailer credentials (see `packaging/docker/docker-compose-base.yml`). Keep secrets out of the committed INI file and provide them via the environment.

## Next steps
- [Migrations](../core-features/migrations/index.md) — schema management and the migration CLI.
- [Configuration](../core-features/configuration.md) — every available setting.
- [MVC](../core-features/mvc.md) and [Routing](../core-features/routing.md) — build your first pages.
