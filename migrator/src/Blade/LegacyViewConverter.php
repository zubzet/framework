<?php

    namespace ZubZet\Tooling\Blade;

    /**
     * Converts a legacy ZubZet view/layout (the "return type" syntax) into a
     * Katana .blade.php document, as part of the 1.3.0 upgrade.
     *
     * Views:
     *   return ["head" => function($opt){ ?>...<?php }, "body" => function($opt){ ?>...<?php }];
     *     - body only            -> the raw body content (straight PHP/HTML, no wrapper)
     *     - head (+ body)        -> @section('head')...@endsection [+ @section('body')...]
     *
     * Layouts:
     *   return ["layout" => function($opt, $body, $head){ ?>... $body($opt) ... $head($opt) ...<?php }];
     *     - calls to the $body / $head closure params become @yield('body') / @yield('head');
     *       everything else (including the $opt["layout_essentials_*"] calls) stays raw PHP.
     *
     * Literal Blade-significant sequences ({{, {!!, {{--) in template text are escaped
     * with Blade's own escape syntax (@{{ ...) so Katana passes them through unchanged.
     *
     * Extraction is tokenizer-based (token_get_all) so it is robust against braces,
     * open/close-tag boundaries and nested control structures inside the closures.
     */
    final class LegacyViewConverter {

        /** True when the source is a legacy layout (returns a "layout" closure). */
        public static function isLayout(string $source): bool {
            return (bool) preg_match('/return\s*\[\s*["\']layout["\']\s*=>/', $source);
        }

        /** True when the source looks like a legacy return[...] view/layout at all. */
        public static function isLegacyView(string $source): bool {
            return (bool) preg_match('/return\s*\[/', $source);
        }

        /**
         * Convert any legacy view or layout. Returns the .blade.php document.
         * @throws \RuntimeException when the source is not a convertible legacy view.
         */
        public static function convertFile(string $source): string {
            return self::isLayout($source)
                ? self::convertLayout($source)
                : self::convert($source);
        }

        /** Convert a legacy view (head/body sections). */
        public static function convert(string $source, bool $neutralize = true): string {
            $parsed = self::extractSections($source);
            $sections = $parsed["sections"];
            $order = $parsed["order"];

            $neut = fn(string $s): string => $neutralize ? self::neutralizeBladeTokens($s) : $s;

            // body-only -> straight content, dedented to column 0 (no wrapping section).
            if ($order === ["body"] || $order === []) {
                return self::dedent(ltrim($neut($sections["body"] ?? ""), "\n"));
            }

            // head (+ body) and any additional keys -> sections. The content keeps its
            // original indentation so it stays nested under the @section directive.
            $out = "";
            $keys = array_values(array_unique(array_merge(["head", "body"], $order)));
            foreach ($keys as $key) {
                if (!array_key_exists($key, $sections)) continue;
                $content = trim($neut($sections[$key]), "\n");
                $out .= "@section(\"$key\")\n" . $content . "\n@endsection\n\n";
            }
            return rtrim($out) . "\n";
        }

        /** Convert a legacy layout (yields for the body/head closure params). */
        public static function convertLayout(string $source, bool $neutralize = true): string {
            [$params, $body] = self::extractClosure($source, "layout");
            // positional convention: function($opt, $body, $head)
            $bodyVar = $params[1] ?? null;
            $headVar = $params[2] ?? null;

            if ($neutralize) $body = self::neutralizeBladeTokens($body);

            $yield = function(string $tpl, ?string $var, string $section): string {
                if (!$var) return $tpl;
                $v = preg_quote($var, '/');
                // Match an echo/statement tag whose sole payload is a call to the closure
                // param, e.g. the short-echo form, the "echo" form, or a bare statement form.
                $pat = '/<\?(?:=|php)\s*(?:echo\s+)?\\' . '$' . $v . '\s*\([^)]*\)\s*;?\s*\?>/';
                return preg_replace($pat, "@yield(\"$section\")", $tpl);
            };
            $body = $yield($body, $bodyVar, "body");
            $body = $yield($body, $headVar, "head");

            return self::dedent(ltrim($body, "\n"));
        }

        /**
         * Remove one level of leading indentation (the wrapper closure's indent) so the
         * migrated document sits at column 0. Relative indentation is preserved. A first
         * line left inline with the closing ?> tag has a smaller indent than the block and
         * is treated as an outlier so it does not shrink the common indent to remove.
         */
        private static function dedent(string $content): string {
            $lines = explode("\n", $content);

            $indents = [];
            foreach ($lines as $i => $line) {
                if (trim($line) === "") continue;
                preg_match('/^[ \t]*/', $line, $m);
                $indents[$i] = strlen($m[0]);
            }
            if (empty($indents)) return $content;

            // Drop an inline first line (smaller indent than the rest) from the calculation.
            if (array_key_first($indents) === 0 && count($indents) > 1) {
                $rest = $indents;
                unset($rest[0]);
                if ($indents[0] < min($rest)) unset($indents[0]);
            }

            $common = min($indents);
            if ($common === 0) return $content;

            return implode("\n", array_map(function(string $line) use ($common): string {
                $j = 0;
                $len = strlen($line);
                while ($j < $common && $j < $len && ($line[$j] === " " || $line[$j] === "\t")) $j++;
                return substr($line, $j);
            }, $lines));
        }

        /**
         * Neutralise literal Blade tokens so Katana emits them verbatim.
         *   {{-- .. --}}  -> wrapped in @verbatim (the @{{-- escape does not work
         *                    because comments are stripped before echo-escaping)
         *   {!! .. !!}    -> @{!! .. !!}   (Blade raw-echo escape)
         *   {{ .. }}      -> @{{ .. }}     (Blade echo escape)
         * Raw <?php ?> still executes inside @verbatim, so wrapping is output-preserving.
         */
        public static function neutralizeBladeTokens(string $tpl): string {
            $tpl = preg_replace('/\{\{--.*?--\}\}/s', '@verbatim$0@endverbatim', $tpl);
            $tpl = str_replace('{!!', '@{!!', $tpl);
            $tpl = preg_replace('/(?<!@)\{\{(?!--)/', '@{{', $tpl);
            return $tpl;
        }

        /** @return array{sections: array<string,string>, order: string[]} */
        public static function extractSections(string $source): array {
            $tokens = token_get_all($source);
            $n = count($tokens);

            $i = 0;
            for (; $i < $n; $i++) {
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_RETURN) { $i++; break; }
            }
            for (; $i < $n; $i++) {
                $t = $tokens[$i];
                if ($t === '[' || (is_array($t) && $t[0] === T_ARRAY)) break;
            }
            if ($i >= $n) {
                throw new \RuntimeException("no top-level return[...] found");
            }
            if ($tokens[$i] === '[') { $i++; }
            else { throw new \RuntimeException("only short-array return[...] views are supported"); }

            $sections = [];
            $order = [];

            while ($i < $n) {
                for (; $i < $n; $i++) {
                    $t = $tokens[$i];
                    if ($t === ']') break 2;
                    if ($t === ',') continue;
                    if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
                    break;
                }
                if ($i >= $n || $tokens[$i] === ']') break;

                if (!(is_array($tokens[$i]) && $tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING)) {
                    throw new \RuntimeException("expected a section key string, got: " . self::describe($tokens[$i]));
                }
                $key = trim($tokens[$i][1], "\"'");
                $i++;

                for (; $i < $n; $i++) {
                    if (is_array($tokens[$i]) && $tokens[$i][0] === T_DOUBLE_ARROW) { $i++; break; }
                }
                for (; $i < $n; $i++) {
                    if (is_array($tokens[$i]) && ($tokens[$i][0] === T_FUNCTION || $tokens[$i][0] === T_FN)) break;
                }
                $depthParen = 0; $sawParams = false;
                for (; $i < $n; $i++) {
                    $t = $tokens[$i];
                    if ($t === '(') { $depthParen++; $sawParams = true; }
                    elseif ($t === ')') { $depthParen--; }
                    elseif ($t === '{' && $depthParen === 0 && $sawParams) break;
                }
                $i++; // into body
                $bodyTokens = []; $depth = 0;
                for (; $i < $n; $i++) {
                    $t = $tokens[$i];
                    if ($t === '{') { $depth++; }
                    elseif ($t === '}') {
                        if ($depth === 0) { $i++; break; }
                        $depth--;
                    }
                    $bodyTokens[] = $t;
                }

                $sections[$key] = self::detokenizeTemplate($bodyTokens);
                $order[] = $key;
            }

            return ["sections" => $sections, "order" => $order];
        }

        /** @return array{0: string[], 1: string} [paramVarNames, closureBodyTemplate] */
        private static function extractClosure(string $source, string $key): array {
            $tokens = token_get_all($source);
            $n = count($tokens);
            $i = 0;
            for (; $i < $n; $i++) if (is_array($tokens[$i]) && $tokens[$i][0] === T_RETURN) { $i++; break; }
            for (; $i < $n; $i++) if ($tokens[$i] === '[') { $i++; break; }
            for (; $i < $n; $i++) {
                $t = $tokens[$i];
                if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING && trim($t[1], "\"'") === $key) break;
            }
            for (; $i < $n; $i++) if (is_array($tokens[$i]) && ($tokens[$i][0] === T_FUNCTION || $tokens[$i][0] === T_FN)) break;
            $params = []; $depthParen = 0; $sawParams = false; $inParams = false;
            for (; $i < $n; $i++) {
                $t = $tokens[$i];
                if ($t === '(') { $depthParen++; $sawParams = true; $inParams = ($depthParen === 1); continue; }
                if ($t === ')') { $depthParen--; if ($depthParen === 0) $inParams = false; continue; }
                if ($inParams && is_array($t) && $t[0] === T_VARIABLE) $params[] = ltrim($t[1], '$');
                if ($t === '{' && $depthParen === 0 && $sawParams) break;
            }
            $i++; // into body
            $bodyTokens = []; $depth = 0;
            for (; $i < $n; $i++) {
                $t = $tokens[$i];
                if ($t === '{') $depth++;
                elseif ($t === '}') { if ($depth === 0) break; $depth--; }
                $bodyTokens[] = $t;
            }
            return [$params, self::detokenizeTemplate($bodyTokens)];
        }

        /** Reconstruct template text from closure-body tokens, stripping the wrapping tags. */
        private static function detokenizeTemplate(array $bodyTokens): string {
            while ($bodyTokens && is_array($bodyTokens[0]) && $bodyTokens[0][0] === T_WHITESPACE) {
                array_shift($bodyTokens);
            }
            if ($bodyTokens && is_array($bodyTokens[0]) && $bodyTokens[0][0] === T_CLOSE_TAG) {
                array_shift($bodyTokens);
            }
            while ($bodyTokens && is_array($bodyTokens[count($bodyTokens) - 1]) && $bodyTokens[count($bodyTokens) - 1][0] === T_WHITESPACE) {
                array_pop($bodyTokens);
            }
            if ($bodyTokens && is_array($bodyTokens[count($bodyTokens) - 1])
                && in_array($bodyTokens[count($bodyTokens) - 1][0], [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO], true)) {
                array_pop($bodyTokens);
            }

            $out = "";
            foreach ($bodyTokens as $t) {
                $out .= is_array($t) ? $t[1] : $t;
            }
            return $out;
        }

        private static function describe($t): string {
            if (is_array($t)) return token_name($t[0]) . "(" . trim($t[1]) . ")";
            return "'$t'";
        }
    }

?>
