# Link the skill into the project

This skill ships inside the `zubzet/framework` composer package, so every project gets the version that matches its installed framework. Claude Code only loads it automatically when the project links it. The first time you are pointed at this skill explicitly (by path, or by the installation docs) and the project does not link it yet, create the link from the project root:

```bash
mkdir -p .claude/skills
ln -s ../../vendor/zubzet/framework/skills/zubzet .claude/skills/zubzet
```

- Use exactly this relative target. It follows the framework version from `composer.lock`, so a `composer update` updates the skill too, and the link works on every machine and in CI.
- Only create it when `.claude/skills/zubzet` does not exist. If it exists as a real directory or points somewhere else, leave it alone and tell the user.
- Tell the user you created it and that it should be committed. Do not commit it yourself unless asked.
- Skip this step inside the framework repository itself. Neither the installer nor the version migrator creates the link on purpose: a `.claude` folder is one tool's convention, so the project decides whether it wants it.
- The public entry point for agents without any context is <https://zubzet.com/agent.md>; it sends them to this skill and offers the same link.
