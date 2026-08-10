<?php

    namespace ZubZet\Framework\Registry;

    /**
     * Describes one resolvable kind of framework component: where it lives in
     * userspace, inside a module, and inside the framework itself.
     */
    final class Kind {

        public function __construct(
            /** Kind identifier, e.g. "controllers". */
            public string $name,
            /** Config key holding the userspace root (e.g. "z_controllers"), or null. */
            public ?string $userspaceConfigKey,
            /** Fixed userspace root when no config key exists (e.g. "./app/Database/seed"). */
            public ?string $userspacePath,
            /** Root-relative path inside a module, e.g. "app/Controllers". */
            public string $moduleSubPath,
            /** Path under z_framework_root, or null when the framework ships none (seeds). */
            public ?string $frameworkSubPath,
            /** Extensions probed by find(), in order. */
            public array $extensions = [".php"],
            /** Whether find() may fall back to the recursive per-root index. */
            public bool $deepFind = true,
            /** Whether files() enumerates recursively (false = flat glob, like routes). */
            public bool $deepFiles = true,
        ) {}

        public function userspaceRoot(): string {
            if(!is_null($this->userspaceConfigKey)) return config($this->userspaceConfigKey);
            return $this->userspacePath;
        }
    }

?>
