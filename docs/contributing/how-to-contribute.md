# How To Contribute

## Git Workflow

### Branching model

The framework uses a two-tier integration flow:

| Branch | Stability | Source of changes |
| ------ | --------- | ----------------- |
| `develop` | **Unstable.** Active integration branch. Features land here once reviewed. May contain in-progress combinations that haven't been validated together. | Feature-branch PRs |
| `main` | **Release-candidate.** Mostly working, comparable to an RC. Only merged to from `develop` when the combined state is green and reviewed. | Promotion PRs from `develop` |

Practical implications:

- Feature work targets `develop`. PRs may be opened as **draft / WIP** while in progress.
- Promotions to `main` happen as a separate PR (`develop` → `main`) when the maintainer is satisfied with the integrated state.
- CI runs the full PHP matrix (8.0–8.5) on pushes to `develop`, `main`, and version tags. PRs and other branches run an extremes-only smoke (8.0 + 8.5).

!!! note "Release candidates"
    During a release-candidate phase (for example **v1.2.0-RC1**), allow prerelease stability so
    Composer can resolve the RC:

    ```bash
    composer create-project zubzet/zubzet your-folder-name --stability=RC
    ```

### Setup

- **Origin** (your fork): `git@github.com:your-username/framework.git`
- **Upstream** (main repo): `git@github.com:zubzet/framework.git`

### Common Workflows

#### Update your local develop branch from upstream

```bash
git fetch upstream
git checkout develop
git merge upstream/develop
```

#### Create and push a feature branch

```bash
git checkout -b feature/your-feature-name
# Make your changes...
git add .
git commit -m "Your commit message"
git push origin feature/your-feature-name
```

#### Create a Pull Request on GitHub

1. Go to `https://github.com/zubzet/framework`
2. Click "New Pull Request"
3. Select `develop` as base branch
4. Select your feature branch as compare branch
5. Create PR and request reviews — open as draft while still in progress

#### Sync your fork with upstream

If develop has changed while you're working on a feature branch:

```bash
git fetch upstream
git rebase upstream/develop
git push origin feature/your-feature-name -f
```

#### Keep your develop branch in sync

```bash
git checkout develop
git fetch upstream
git merge upstream/develop
git push origin develop
```

#### Promote develop to main (maintainer)

```bash
git fetch upstream
git checkout main
git merge upstream/main
git merge upstream/develop
git push origin main
# then open the PR upstream and let the full matrix run
```

### Important Notes

- Always work on feature branches, never directly on `develop` or `main`.
- Feature PRs target `develop`; only `develop` → `main` PRs target `main`.
- Keep your fork updated frequently to avoid conflicts.
- Push to `origin` (your fork), create PR to `upstream` (main repo).

## Commit Messages
We previously used [gitmoji](https://gitmoji.dev/) for commits.
From now on, we use [Conventional Commits](https://www.conventionalcommits.org/) instead, to respond to feedback and align with standard practices.

**Format:**
```<type>(<scope>): <short summary>```

**Examples:**
- `feat: add a query builder`
- `chore(dev): setup docker development environment`
- `test: migrate all qa-suite tests`

## Local Development Environment

The dockerized test app under `tests/e2e/` doubles as the local development environment. From `tests/e2e/`:

- `npm run start` — bootstrap the stack: `npm install`, `docker compose up -d --build`, `composer install`, run seeds, print startup info. Returns to the shell when ready.
- `npm run startup` — same bootstrap as `start`, then keeps `docker compose up` attached in the foreground so logs stream live. Exit (Ctrl-C / closing the terminal) tears the containers down.
- `npm run stop` — `docker compose down -v`.

### VSCode Tasks

Both the framework root (`.vscode/tasks.json`) and the e2e folder (`tests/e2e/.vscode/tasks.json`) ship the same two tasks, so the auto-start fires whichever folder you open in VSCode:

- **Start Dev Environment** — runs `npm run startup` automatically on `folderOpen`, bringing the stack up and attaching to it.
- **Stop Dev Environment** — runs `npm run stop` on demand.

Both tasks render in a dedicated panel. On first open VSCode prompts to **Allow Automatic Tasks** — accept it, otherwise the auto-start is silently suppressed (re-enable later via `Tasks: Manage Automatic Tasks`).

Closing the VSCode window does **not** stop the stack — VSCode kills task terminals too aggressively for any cleanup hook to run reliably. Before opening the next project, run **Stop Dev Environment** from the command palette (or `npm run stop` from `tests/e2e/`) to free the ports.

## Documentation
1. **Clone the Repository**
```bash
git clone https://github.com/zubzet/framework.git
```

2. **Start a Live Preview:**
Use the following Docker command to start a live [preview of the documentation](http://127.0.0.1:8000/docs/) on port 8000:
```bash
docker run --rm -it -p 8000:8000 -v ${PWD}:/docs --entrypoint sh squidfunk/mkdocs-material -c "pip install mike 'click<8.3' && mkdocs serve -a 0.0.0.0:8000"
```

3. **Make Changes:**
Edit or add files under the /docs directory. Any changes will be automatically reflected in the live preview.

4. **Submit a Pull Request:**
Once you’ve made your changes, push them to your fork and create a pull request to contribute your improvements.

<br>
Thank you for helping improve the ZubZet framework!