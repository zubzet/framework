<?php

    namespace ZubZet\Tooling\AutomatedChanges\V1_3_0;

    /**
     * Finds the render() calls that are not the last thing their function does.
     * Since 1.3.0 render() ends the request, so everything after it is skipped.
     *
     * A call is reported when it is not returned and
     * - code of the same function can still run after it (a second render, a log entry, ...), or
     * - it sits in a loop, which now stops after the first view, or
     * - the function buffers output (ob_start) to capture the view.
     */
    class CodeAfterRender {

        private const RECEIVERS = ['$res', '$response'];

        private const INSIGNIFICANT = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML, T_OPEN_TAG, T_CLOSE_TAG];

        private const LOOPS = [T_FOR, T_FOREACH, T_WHILE, T_DO];

        // Blocks that belong to the statement before them, so they are no code that runs after it
        private const ALTERNATIVE_BRANCHES = [T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY];

        /**
         * @return int[] The line of every render() call that something still depends on
         */
        public static function lines(string $source): array {
            try {
                $tokens = token_get_all($source);
            } catch(\Throwable) {
                return [];
            }

            $lines = [];

            // The kind of every open block, "function", "loop" or "other", with the index of its brace
            $blocks = [];
            $pending = "other";

            foreach($tokens as $i => $token) {
                $id = is_array($token) ? $token[0] : $token;

                if(T_FUNCTION === $id) $pending = "function";
                if(in_array($id, self::LOOPS, true)) $pending = "loop";
                if(";" === $id) $pending = "other";

                if("{" === $id || T_CURLY_OPEN === $id || T_DOLLAR_OPEN_CURLY_BRACES === $id) {
                    $blocks[] = ["kind" => "{" === $id ? $pending : "other", "at" => $i];
                    $pending = "other";
                    continue;
                }

                if("}" === $id) {
                    array_pop($blocks);
                    continue;
                }

                if(!self::isRender($tokens, $i)) continue;

                // The innermost function around the call, and the loops between the two
                $function = null;
                $inLoop = false;
                foreach(array_reverse($blocks) as $block) {
                    if("function" === $block["kind"]) {
                        $function = $block["at"];
                        break;
                    }
                    if("loop" === $block["kind"]) $inLoop = true;
                }
                if(is_null($function)) continue;

                if(self::isReturned($tokens, $i, $function)) continue;

                $functionEnd = self::blockEnd($tokens, $function);
                $affected = $inLoop
                    || self::buffersOutput($tokens, $function, $i)
                    || self::codeFollows($tokens, $i, $functionEnd);

                if($affected) $lines[] = $token[2];
            }

            return $lines;
        }

        // True at the "->" of $res->render( and response()->render(, and at the name of view(
        private static function isRender(array $tokens, int $i): bool {
            $token = $tokens[$i];
            if(!is_array($token)) return false;

            if(T_STRING === $token[0] && "view" === $token[1]) {
                if("(" !== ($tokens[self::next($tokens, $i + 1)] ?? null)) return false;

                // A method or a declaration with that name is something else
                $before = $tokens[self::previous($tokens, $i - 1)] ?? null;
                $beforeId = is_array($before) ? $before[0] : $before;
                return !in_array($beforeId, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], true);
            }

            if(!in_array($token[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) return false;

            $method = $tokens[$i + 1] ?? null;
            if(!is_array($method) || "render" !== $method[1]) return false;
            if("(" !== ($tokens[$i + 2] ?? null)) return false;

            $receiver = $tokens[$i - 1];
            if(is_array($receiver)) return in_array($receiver[1], self::RECEIVERS, true);

            $helper = $tokens[$i - 3] ?? null;
            return ")" === $receiver && is_array($helper) && "response" === $helper[1];
        }

        // "return $res->render(...)" leaves the function anyway
        private static function isReturned(array $tokens, int $i, int $function): bool {
            for($at = $i - 1; $at > $function; $at--) {
                if(in_array($tokens[$at], [";", "{", "}"], true)) return false;
                if(is_array($tokens[$at]) && T_RETURN === $tokens[$at][0]) return true;
            }

            return false;
        }

        private static function buffersOutput(array $tokens, int $function, int $i): bool {
            for($at = $function; $at < $i; $at++) {
                if(is_array($tokens[$at]) && "ob_start" === $tokens[$at][1]) return true;
            }

            return false;
        }

        // Follows the control flow from the end of the statement to the end of the function
        private static function codeFollows(array $tokens, int $i, int $functionEnd): bool {
            // The end of the statement
            $depth = 0;
            for($at = $i; $at < $functionEnd; $at++) {
                $token = is_array($tokens[$at]) ? $tokens[$at][1] : $tokens[$at];
                if(in_array($token, ["(", "[", "{"], true)) $depth++;
                if(in_array($token, [")", "]", "}"], true)) $depth--;
                if(";" === $token && 0 === $depth) break;
            }

            $at = self::next($tokens, $at + 1);
            while($at < $functionEnd) {
                $id = is_array($tokens[$at]) ? $tokens[$at][0] : $tokens[$at];

                // The end of a block the call sits in, the flow goes on behind it
                if("}" === $id) {
                    $at = self::next($tokens, $at + 1);
                    continue;
                }

                if(in_array($id, self::ALTERNATIVE_BRANCHES, true)) {
                    while($at < $functionEnd && "{" !== $tokens[$at] && ";" !== $tokens[$at]) $at++;

                    // A branch without braces, too unusual to follow
                    if("{" !== $tokens[$at]) return true;

                    $at = self::next($tokens, self::blockEnd($tokens, $at) + 1);
                    continue;
                }

                if(in_array($id, [T_RETURN, T_EXIT], true)) return false;

                // The other cases of a switch do not run after a break
                if(T_BREAK === $id) {
                    $at = self::switchEnd($tokens, $at, $functionEnd);
                    continue;
                }

                return true;
            }

            return false;
        }

        // Index of the brace that closes the block a break statement at $at leaves
        private static function switchEnd(array $tokens, int $at, int $functionEnd): int {
            $depth = 0;
            for(; $at < $functionEnd; $at++) {
                $token = is_array($tokens[$at]) ? $tokens[$at][1] : $tokens[$at];
                if("{" === $token) $depth++;
                if("}" === $token && --$depth < 0) return $at;
            }

            return $at;
        }

        // Index of the next token that is no whitespace and no comment
        private static function next(array $tokens, int $i): int {
            while(is_array($tokens[$i] ?? null) && in_array($tokens[$i][0], self::INSIGNIFICANT, true)) $i++;
            return $i;
        }

        private static function previous(array $tokens, int $i): int {
            while($i > 0 && is_array($tokens[$i]) && in_array($tokens[$i][0], self::INSIGNIFICANT, true)) $i--;
            return $i;
        }

        // Index of the brace that closes the block opened at $open
        private static function blockEnd(array $tokens, int $open): int {
            $depth = 0;
            for($i = $open; isset($tokens[$i]); $i++) {
                $token = $tokens[$i];
                $opens = "{" === $token || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true));
                if($opens) $depth++;
                if("}" === $token && 0 === --$depth) return $i;
            }

            return $i;
        }
    }

?>
