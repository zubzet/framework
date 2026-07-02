<?php

    namespace ZubZet\Framework\Rendering;

    use Blade\Blade;

    /**
     * Thin adapter around the Katana Blade engine.
     *
     * The framework selects the layout externally (per request / HandlesDefaultLayout),
     * whereas Blade expects a view to @extends its layout. We bridge that by composing a
     * small synthetic child at render time: `@extends(<layout>)` followed by the view's
     * sections (or the whole body wrapped in `@section('body')` for section-less views).
     *
     * Everything the adapter has to do beyond `new Blade(...); render(...)` marks a gap in
     * Katana's current integration surface (see docs findings for katana#53):
     *   - a Blade instance per render, because Blade takes a single view path (no
     *     pluggable view finder for the userspace/framework override chain) and because
     *     its template-inheritance state (@section content) lives on the instance and is
     *     not reset between top-level renders — reusing an instance leaks sections;
     *   - a synthetic child file on disk, because there is no render-from-string / no way
     *     to inject a layout programmatically;
     *   - relies on the "forward render data to the parent layout" patch so the layout
     *     can read $opt (root/title/essentials).
     */
    class KatanaRenderer {

        public function __construct(private string $cachePath) {
            if(!is_dir($this->cachePath)) @mkdir($this->cachePath, 0777, true);
        }

        /**
         * @param string $viewFile   absolute path to the resolved view .blade.php
         * @param string $layoutFile absolute path to the resolved layout .blade.php
         * @param string $root       the view root the layout lives under
         * @param array  $data       render data (passed through Blade's extract())
         */
        public function render(string $viewFile, string $layoutFile, string $root, array $data): string {
            $root = rtrim($root, "/");
            // A fresh engine per render: Blade keeps @section state on the instance and
            // never resets it, so a shared instance would leak sections across views.
            // The on-disk compiled-view cache ($cachePath) is still reused.
            $blade = new Blade($root, $this->cachePath);

            $layoutName = $this->nameOf($layoutFile, $root);
            $viewSource = (string) file_get_contents($viewFile);

            $child = "@extends(\"{$layoutName}\")\n";
            $child .= str_contains($viewSource, "@section(")
                ? $viewSource
                : "@section(\"body\")\n{$viewSource}\n@endsection\n";

            return (string) $blade->renderViewFile($this->composeFile($viewFile, $layoutFile, $child), $data);
        }

        /** Dotted view name of $file relative to $root, without the .blade.php suffix. */
        private function nameOf(string $file, string $root): string {
            $rel = ltrim(substr($file, strlen($root)), "/");
            return str_replace("/", ".", preg_replace('/\.blade\.php$/', "", $rel));
        }

        /** Write the synthetic child; stable path + rewrite-on-change so Blade caches it. */
        private function composeFile(string $viewFile, string $layoutFile, string $source): string {
            $dir = "{$this->cachePath}/__compose";
            if(!is_dir($dir)) @mkdir($dir, 0777, true);

            $file = "{$dir}/" . md5($viewFile . "|" . $layoutFile) . ".blade.php";
            if(!is_file($file) || file_get_contents($file) !== $source) {
                file_put_contents($file, $source);
            }
            return $file;
        }
    }

?>
