# Git Workflow for Framework Development

## Setup Complete ✓
- **Origin** (your fork): `git@github.com:alexander-zierhut/framework.git`
- **Upstream** (main repo): `git@github.com:zubzet/framework.git`

## Common Workflows

### 1. Update your local main branch from upstream
```bash
git fetch upstream
git checkout main
git merge upstream/main
```

### 2. Create and push a feature branch
```bash
git checkout -b feature/your-feature-name
# Make your changes...
git add .
git commit -m "Your commit message"
git push origin feature/your-feature-name
```

### 3. Create a Pull Request on GitHub
- Go to `https://github.com/zubzet/framework`
- Click "New Pull Request"
- Select `main` as base branch
- Select your feature branch as compare branch
- Create PR and request reviews

### 4. Sync your fork with upstream (if main has changed)
```bash
git fetch upstream
git rebase upstream/main
git push origin feature/your-feature-name -f
```

### 5. Keep your main branch in sync
```bash
git checkout main
git fetch upstream
git merge upstream/main
git push origin main
```

## Notes
- Always work on feature branches, never directly on main
- Keep your fork updated frequently to avoid conflicts
- Push to `origin` (your fork), create PR to `upstream` (main repo)
