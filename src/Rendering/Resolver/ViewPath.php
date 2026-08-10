<?php

    namespace ZubZet\Framework\Rendering\Resolver;

    /**
     * @internal
     * View-name helper. The actual lookup (user space -> framework, dot notation, ".blade.php")
     * is done by the render engine's Katana finder chain; this only covers what Katana can't:
     * stripping a legacy extension off a view reference.
     */
    trait ViewPath {

        /**
         * @internal
         * Normalize a view reference to a bare Katana view name
         *
         * File extensions are not needed, e.g. "index", "index.blade.php" and "index.php" are all
         * equivalent. Dot notation is supported, e.g. "core.index" resolves like "core/index".
         *
         * @param string $document A view reference, with or without an extension
         * @return string The bare view name Katana resolves against its finders
         */
        public static function viewName(string $document): string {
            $document = rtrim($document);
            foreach ([".blade.php", ".php"] as $suffix) {
                if(str_ends_with($document, $suffix)) {
                    return substr($document, 0, -strlen($suffix));
                }
            }
            return $document;
        }
    }

?>
