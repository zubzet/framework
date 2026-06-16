# How To Release

This page documents how a maintainer cuts a **release candidate (RC)** for a minor
version, for example `v1.2.0-RC2`. It assumes push access to `upstream`
(`zubzet/framework`) and an authenticated [`gh`](https://cli.github.com/) CLI.

For the everyday feature-branch flow and the `develop` / `main` model, see
[How To Contribute](how-to-contribute).

## Branch & tag model

| Ref | Role |
| --- | ---- |
| `develop` | Integration branch. Everything merges here first via feature-branch PRs. |
| `main` | **Release staging.** `develop` is merged here and tested; this is the quality gate. Transient: it is recreated each release cycle (it may have been deleted after the previous release). |
| `<minor>.x` (e.g. `1.2.x`) | The release line for that minor version. **Tags live on this branch.** |
| `v<x.y.z>` / `v<x.y.z>-RCn` | Lightweight tag on the `<minor>.x` tip, each with a matching GitHub release. |

!!! note "RCs are pre-releases"
    Publish every RC as a GitHub **pre-release**. That keeps the last stable release
    (e.g. `v1.1.0`) labelled *Latest* and lets consumers opt in with
    `composer create-project zubzet/zubzet your-folder --stability=RC`.

## Prerequisites

- Every release-blocking change is merged into `develop`. Track the open items in a
  release issue (e.g. [#171](https://github.com/zubzet/framework/issues/171)) and confirm
  each is done before starting.
- You can push to `upstream`, and `gh` is authenticated against `zubzet/framework`.
- Always source from `upstream/develop`, never a possibly-stale local `develop`:

    ```bash
    git fetch upstream --tags
    ```

## Cutting an RC

The worked example below cuts `v1.2.0-RC2`, where `1.2.x` already exists from RC1.

### 1. Stage on `main` and trigger the gate

Recreate `main` from the current release tip and merge `develop` into it. Resolving any
conflicts here (not on the release branch) means the release branch only ever
fast-forwards onto a tested state.

```bash
git switch -c main upstream/1.2.x
git merge --no-ff upstream/develop -m "Merge develop into main for v1.2.0-RC2"
git push upstream main:main          # triggers the E2E matrix + unstable-docs deploy
```

!!! tip "First RC of a new minor"
    When the `<minor>.x` branch does not exist yet, stage `main` from `develop`
    (`git switch -c main upstream/develop`), then create the release branch from `main`
    (`git push upstream main:refs/heads/1.3.x`) before tagging.

### 2. Wait for the tests

`main` is the gate; only promote once its **E2E tests** run is green:

```bash
git fetch upstream --tags
gh run list --branch main --limit 5
gh run watch <run-id> --exit-status
```

### 3. Promote to the release branch

```bash
git switch -c 1.2.x upstream/1.2.x
git merge --no-ff main -m "Merge main into 1.2.x for v1.2.0-RC2"
git push upstream 1.2.x:1.2.x
```

Sanity-check that the release branch now matches `develop`:
`git diff --stat 1.2.x upstream/develop` should be empty.

### 4. Tag the release commit (name only)

Use a **lightweight** tag (no `-a`/`-m`, so no description) on the `1.2.x` tip:

```bash
git tag v1.2.0-RC2 1.2.x
git push upstream v1.2.0-RC2         # triggers E2E + a versioned docs deploy
```

### 5. Create the GitHub pre-release

```bash
gh release create v1.2.0-RC2 --prerelease --verify-tag \
  --title v1.2.0-RC2 --notes-file notes.md
```

For RC2 and later, build the notes from the previous RC: keep its full changelog, add a
**"Changes since RCn-1"** section for the newly merged PRs, append those PRs to the
*What's Changed* list, and update the `compare/...` links (e.g. `v1.1.0...v1.2.0-RC2`
plus `v1.2.0-RC1...v1.2.0-RC2`).

### 6. Demote the previous RC's notes

Keep the full notes only on the newest RC. Trim the previous one to a one-line pointer and
leave it marked as a pre-release:

```bash
gh release edit v1.2.0-RC1 \
  --notes "The first release candidate for v1.2.0. Superseded by v1.2.0-RC2; see that release for the current notes."
```

## CI / automation side effects

Know what each push sets off (see `.github/workflows/`):

- **`tests_e2e.yml`** runs the PHP 8.0 to 8.5 matrix on a push to **any branch** and on
  `v*.*.*` **tags** (path-filtered to `src/`, `web/`, `tests/e2e/`, `composer*`, and the
  workflow file).
- **`docs.yml`**:
    - push to **`main`** deploys docs to the **`unstable`** channel.
    - push of a **`v*.*.*` tag** deploys that version's docs **and promotes it to
      `latest` / default** on the public docs site. This fires for RC tags too, so keep it
      in mind when tagging a pre-release.

## Gotchas

- `main` is recreated each cycle, so don't assume it exists; create it from the release
  tip (or from `develop` for a brand-new minor).
- The RC-packaging docs and `develop` can carry the same files; merging through `main`
  keeps the three-way merges clean and leaves the release branch byte-identical to
  `develop`.
- Promoting a stable (non-RC) release follows the same steps without `--prerelease`; the
  versioned docs deploy already promotes it to *Latest*.
