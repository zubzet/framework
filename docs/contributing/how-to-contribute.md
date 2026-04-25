# How To Contribute

## Git Workflow

### Setup

- **Origin** (your fork): `git@github.com:your-username/framework.git`
- **Upstream** (main repo): `git@github.com:zubzet/framework.git`

### Common Workflows

#### Update your local main branch from upstream

```bash
git fetch upstream
git checkout main
git merge upstream/main
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
3. Select `main` as base branch
4. Select your feature branch as compare branch
5. Create PR and request reviews

#### Sync your fork with upstream

If main has changed while you're working on a feature branch:

```bash
git fetch upstream
git rebase upstream/main
git push origin feature/your-feature-name -f
```

#### Keep your main branch in sync

```bash
git checkout main
git fetch upstream
git merge upstream/main
git push origin main
```

### Important Notes

- Always work on feature branches, never directly on main
- Keep your fork updated frequently to avoid conflicts
- Push to `origin` (your fork), create PR to `upstream` (main repo)

## Commit Messages
We previously used [gitmoji](https://gitmoji.dev/) for commits.
From now on, we use [Conventional Commits](https://www.conventionalcommits.org/) instead, to respond to feedback and align with standard practices.

**Format:**
```<type>(<scope>): <short summary>```

**Examples:**
- `feat: add a query builder`
- `chore(dev): setup docker development environment`
- `test: migrate all qa-suite tests`

## Documentation
1. **Clone the Repository**
```bash
git clone https://github.com/zubzet/framework.git
```

2. **Start a Live Preview:**
Use the following Docker command to start a live [preview of the documentation](http://127.0.0.1:8000/docs/) on port 8000:
```bash
docker run --rm -it -p 8000:8000 -v ${PWD}:/docs --entrypoint sh squidfunk/mkdocs-material -c "pip install mike && mkdocs serve -a 0.0.0.0:8000"
```

3. **Make Changes:**
Edit or add files under the /docs directory. Any changes will be automatically reflected in the live preview.

4. **Submit a Pull Request:**
Once you’ve made your changes, push them to your fork and create a pull request to contribute your improvements.

<br>
Thank you for helping improve the ZubZet framework!