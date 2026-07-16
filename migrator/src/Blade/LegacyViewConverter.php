<?php

    namespace ZubZet\Tooling\Blade;

    /**
     * Converts a legacy ZubZet view/layout (the "return type" syntax) into a
     * Katana .blade.php document, as part of the 1.3.0 upgrade.
     *
     * Views become template-inheritance children. The old "body" closure turns
     * into @section("content"), an optional "head" closure into @section("head"),
     * and the file @extends the layout picked at render time:
     *
     *     @extends($layout)
     *     @section("head") ... @endsection
     *     @section("content") ... @endsection
     *
     * $layout is supplied as render data (the dotted view name of the chosen layout,
     * e.g. "layout.default_layout" or "rendering.mail_layout"), so the same view works
     * whether the layout is the default, chosen per request, passed explicitly, or
     * lives outside the layout/ directory.
     *
     * Layouts become the parents: the $body($opt) / $head($opt) closure calls turn
     * into @yield("content") / @yield("head"); everything else (including the
     * $opt["layout_essentials_*"] calls) stays raw PHP and is dedented to column 0.
     *
     * Raw PHP (<?php ?> and <?= ?>) is preserved verbatim; Katana keeps it out of
     * the Blade compiler. Literal Blade markers ({{, {!!, {{--) are escaped, but
     * only where they appear in template HTML: inside raw PHP Katana already leaves
     * them untouched, so escaping there would emit a stray "@".
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
                : self::convertView($source);
        }

        /** A legacy view -> an @extends child with head/content sections. */
        public static function convertView(string $source): string {
            $sections = self::extractSections($source)["sections"];

            $out = "@extends(\$layout)\n";
            if(array_key_exists("head", $sections)) {
                $out .= "\n@section(\"head\")\n" . self::section($sections["head"]) . "\n@endsection\n";
            }
            $out .= "\n@section(\"content\")\n" . self::section($sections["body"] ?? "") . "\n@endsection\n";
            return $out;
        }

        /** A legacy layout -> the parent template with @yield placeholders. */
        public static function convertLayout(string $source): string {
            [$params, $body] = self::extractClosure($source, "layout");
            // positional convention: function($opt, $body, $head)
            $body = self::yieldCall($body, $params[1] ?? null, "content");
            $body = self::yieldCall($body, $params[2] ?? null, "head");
            $body = self::essentialsTags($body);

            // A layout has no @section wrapper, so dedent it back to column 0.
            return self::neutralize(self::dedent(ltrim($body, "\n")));
        }

        /** Section body: keep its indentation (it nests under @section), trim blank edges. */
        private static function section(string $content): string {
            return self::neutralize(trim($content, "\n"));
        }

        /**
         * Replace a call to a layout closure param (`<?= $body($opt) ?>`, the "echo"
         * form or a bare statement form) with the matching @yield.
         */
        private static function yieldCall(string $tpl, ?string $var, string $section): string {
            if(!$var) return $tpl;
            $v = preg_quote($var, '/');
            $pat = '/<\?(?:=|php)\s*(?:echo\s+)?\\' . '$' . $v . '\s*\([^)]*\)\s*;?\s*\?>/';
            return preg_replace($pat, "@yield(\"$section\")", $tpl);
        }

        /**
         * Replace the framework's layout-essentials closure calls with the Blade
         * components the framework provides for them (resolved via a shared
         * anonymous-component path), so a layout carries no raw essentials PHP.
         * The components sit under the framework's own "zubzet" component namespace
         * so an app component cannot shadow them by accident.
         *   $opt["layout_essentials_head"]($opt) -> <x-zubzet.head :opt="$opt"/>
         *   $opt["layout_essentials_body"]($opt) -> <x-zubzet.body :opt="$opt"/>
         */
        private static function essentialsTags(string $tpl): string {
            return preg_replace_callback(
                '/<\?(?:php|=)\s*(?:echo\s+)?\$opt\[["\']layout_essentials_(head|body)["\']\]\s*\(\s*\$opt\s*\)\s*;?\s*\?>/',
                fn($m) => "<x-zubzet.{$m[1]} :opt=\"\$opt\"/>",
                $tpl,
            );
        }

        /**
         * Escape literal Blade markers so Katana emits them verbatim, but only in
         * template HTML (T_INLINE_HTML). Markers inside raw PHP are already safe.
         *   {{-- .. --}}  -> wrapped in @verbatim (the @{{-- escape does not work
         *                    because comments are stripped before echo-escaping)
         *   {!! .. !!}    -> @{!! .. !!}
         *   {{ .. }}      -> @{{ .. }}
         */
        public static function neutralize(string $tpl): string {
            $out = "";
            foreach(token_get_all($tpl) as $t) {
                if(is_array($t) && $t[0] === T_INLINE_HTML) {
                    $out .= self::escapeMarkers($t[1]);
                } else {
                    $out .= is_array($t) ? $t[1] : $t;
                }
            }
            return $out;
        }

        private static function escapeMarkers(string $html): string {
            $html = preg_replace('/\{\{--.*?--\}\}/s', '@verbatim$0@endverbatim', $html);
            $html = str_replace('{!!', '@{!!', $html);
            $html = preg_replace('/(?<!@)\{\{(?!--)/', '@{{', $html);
            return $html;
        }

        /**
         * Remove the common leading indentation (the wrapper closure's indent) so a
         * dedented document sits at column 0. A first line left inline with the
         * opening ?> tag has a smaller indent than the block and is treated as an
         * outlier so it does not shrink the common indent to remove.
         */
        private static function dedent(string $content): string {
            $lines = explode("\n", $content);

            $indents = [];
            foreach($lines as $i => $line) {
                if(trim($line) === "") continue;
                preg_match('/^[ \t]*/', $line, $m);
                $indents[$i] = strlen($m[0]);
            }
            if(empty($indents)) return $content;

            if(array_key_first($indents) === 0 && count($indents) > 1) {
                $rest = $indents;
                unset($rest[0]);
                if($indents[0] < min($rest)) unset($indents[0]);
            }

            $common = min($indents);
            if($common === 0) return $content;

            return implode("\n", array_map(function(string $line) use ($common): string {
                $j = 0;
                $len = strlen($line);
                while($j < $common && $j < $len && ($line[$j] === " " || $line[$j] === "\t")) $j++;
                return substr($line, $j);
            }, $lines));
        }

        /** @return array{sections: array<string,string>, order: string[]} */
        public static function extractSections(string $source): array {
            $tokens = token_get_all($source);
            $n = count($tokens);

            $i = 0;
            for(; $i < $n; $i++) {
                if(is_array($tokens[$i]) && $tokens[$i][0] === T_RETURN) { $i++; break; }
            }
            for(; $i < $n; $i++) {
                $t = $tokens[$i];
                if($t === '[' || (is_array($t) && $t[0] === T_ARRAY)) break;
            }
            if($i >= $n) {
                throw new \RuntimeException("no top-level return[...] found");
            }
            if($tokens[$i] === '[') { $i++; }
            else { throw new \RuntimeException("only short-array return[...] views are supported"); }

            $sections = [];
            $order = [];

            while($i < $n) {
                for(; $i < $n; $i++) {
                    $t = $tokens[$i];
                    if($t === ']') break 2;
                    if($t === ',') continue;
                    if(is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
                    break;
                }
                if($i >= $n || $tokens[$i] === ']') break;

                if(!(is_array($tokens[$i]) && $tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING)) {
                    throw new \RuntimeException("expected a section key string, got: " . self::describe($tokens[$i]));
                }
                $key = trim($tokens[$i][1], "\"'");
                $i++;

                for(; $i < $n; $i++) {
                    if(is_array($tokens[$i]) && $tokens[$i][0] === T_DOUBLE_ARROW) { $i++; break; }
                }
                for(; $i < $n; $i++) {
                    if(is_array($tokens[$i]) && ($tokens[$i][0] === T_FUNCTION || $tokens[$i][0] === T_FN)) break;
                }
                $depthParen = 0; $sawParams = false;
                for(; $i < $n; $i++) {
                    $t = $tokens[$i];
                    if($t === '(') { $depthParen++; $sawParams = true; }
                    elseif($t === ')') { $depthParen--; }
                    elseif($t === '{' && $depthParen === 0 && $sawParams) break;
                }
                $i++; // into body
                $bodyTokens = []; $depth = 0;
                for(; $i < $n; $i++) {
                    $t = $tokens[$i];
                    if($t === '{') { $depth++; }
                    elseif($t === '}') {
                        if($depth === 0) { $i++; break; }
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
            for(; $i < $n; $i++) if(is_array($tokens[$i]) && $tokens[$i][0] === T_RETURN) { $i++; break; }
            for(; $i < $n; $i++) if($tokens[$i] === '[') { $i++; break; }
            for(; $i < $n; $i++) {
                $t = $tokens[$i];
                if(is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING && trim($t[1], "\"'") === $key) break;
            }
            for(; $i < $n; $i++) if(is_array($tokens[$i]) && ($tokens[$i][0] === T_FUNCTION || $tokens[$i][0] === T_FN)) break;
            $params = []; $depthParen = 0; $sawParams = false; $inParams = false;
            for(; $i < $n; $i++) {
                $t = $tokens[$i];
                if($t === '(') { $depthParen++; $sawParams = true; $inParams = ($depthParen === 1); continue; }
                if($t === ')') { $depthParen--; if($depthParen === 0) $inParams = false; continue; }
                if($inParams && is_array($t) && $t[0] === T_VARIABLE) $params[] = ltrim($t[1], '$');
                if($t === '{' && $depthParen === 0 && $sawParams) break;
            }
            $i++; // into body
            $bodyTokens = []; $depth = 0;
            for(; $i < $n; $i++) {
                $t = $tokens[$i];
                if($t === '{') $depth++;
                elseif($t === '}') { if($depth === 0) break; $depth--; }
                $bodyTokens[] = $t;
            }
            return [$params, self::detokenizeTemplate($bodyTokens)];
        }

        /** Reconstruct template text from closure-body tokens, stripping the wrapping tags. */
        private static function detokenizeTemplate(array $bodyTokens): string {
            while($bodyTokens && is_array($bodyTokens[0]) && $bodyTokens[0][0] === T_WHITESPACE) {
                array_shift($bodyTokens);
            }
            if($bodyTokens && is_array($bodyTokens[0]) && $bodyTokens[0][0] === T_CLOSE_TAG) {
                array_shift($bodyTokens);
            }
            while($bodyTokens && is_array($bodyTokens[count($bodyTokens) - 1]) && $bodyTokens[count($bodyTokens) - 1][0] === T_WHITESPACE) {
                array_pop($bodyTokens);
            }
            if($bodyTokens && is_array($bodyTokens[count($bodyTokens) - 1])
                && in_array($bodyTokens[count($bodyTokens) - 1][0], [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO], true)) {
                array_pop($bodyTokens);
            }

            $out = "";
            foreach($bodyTokens as $t) {
                $out .= is_array($t) ? $t[1] : $t;
            }
            return $out;
        }

        private static function describe($t): string {
            if(is_array($t)) return token_name($t[0]) . "(" . trim($t[1]) . ")";
            return "'$t'";
        }
    }

?>
