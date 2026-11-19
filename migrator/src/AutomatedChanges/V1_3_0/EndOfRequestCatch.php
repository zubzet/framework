<?php

    namespace ZubZet\Tooling\AutomatedChanges\V1_3_0;

    /**
     * Finds the catches of \Throwable or \Error that also catch the end of a
     * request. Since 1.3.0 a call that ends the request throws EndOfRequest (an
     * \Error) instead of calling exit, so such a catch keeps the action running.
     *
     * A try with an earlier catch of EndOfRequest is fine and not reported.
     */
    class EndOfRequestCatch {

        // Methods of the response and the request that end the request
        private const ENDING_CALLS = [
            "render", "success", "error", "formErrors", "generateRest", "generateRestError",
            "reroute", "rerouteUrl", "logout", "checkPermission", "requireOrganization",
            "requireFreshTwoFactor", "csrfBlockAdditionalMethod",
        ];

        private const RECEIVERS = ['$res', '$response', '$req', '$request'];
        private const RECEIVER_HELPERS = ["response", "request"];

        private const TOO_BROAD = ["Throwable", "Error"];

        private const INSIGNIFICANT = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

        /**
         * @param bool $everyTry Report the catch of every try block, not only of those that visibly end the request
         * @return int[] The line of every catch that also catches the end of the request
         */
        public static function lines(string $source, bool $everyTry = false): array {
            return array_map(
                fn(array $catch) => $catch["line"],
                self::find(self::tokens($source), $everyTry),
            );
        }

        /**
         * Narrows the reported catches to \Exception, which does not see the end of a request:
         * a list with Throwable becomes \Exception, Error is taken out of any other list.
         * A catch of nothing but Error has no such replacement and is left as it is.
         */
        public static function withException(string $source, bool $everyTry = false): string {
            $tokens = self::tokens($source);

            foreach(self::find($tokens, $everyTry) as $catch) {
                $kept = array_filter($catch["names"], fn(string $name) => "Error" !== ltrim($name, "\\"));
                if(in_array("Throwable", array_map(fn(string $name) => ltrim($name, "\\"), $kept), true)) {
                    $kept = ["\\Exception"];
                }
                if(empty($kept)) continue;

                // The first token takes the new list, the rest of the old one goes
                for($i = $catch["from"]; $i <= $catch["to"]; $i++) $tokens[$i] = "";
                $tokens[$catch["from"]] = implode(" | ", $kept);
            }

            return implode("", array_map(
                fn($token) => is_array($token) ? $token[1] : $token,
                $tokens,
            ));
        }

        private static function tokens(string $source): array {
            try {
                return token_get_all($source);
            } catch(\Throwable) {
                return [];
            }
        }

        /**
         * @return array<int, array{line: int, from: int, to: int, names: string[]}> The catches with the token range of their type list
         */
        private static function find(array $tokens, bool $everyTry): array {
            $catches = [];
            foreach($tokens as $i => $token) {
                if(!is_array($token) || T_TRY !== $token[0]) continue;

                $open = self::next($tokens, $i + 1);
                if("{" !== ($tokens[$open] ?? null)) continue;
                $close = self::blockEnd($tokens, $open);

                if(!$everyTry && !self::endsRequest($tokens, $open, $close)) continue;

                // Catches run in order, the first one that matches takes the throwable
                $at = self::next($tokens, $close + 1);
                while(is_array($tokens[$at] ?? null) && T_CATCH === $tokens[$at][0]) {
                    $types = [];
                    for($at++; isset($tokens[$at]) && "{" !== $tokens[$at]; $at++) {
                        if(!is_array($tokens[$at])) continue;
                        if(!in_array($tokens[$at][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) continue;
                        $types[$at] = $tokens[$at][1];
                    }

                    $names = array_map(fn(string $type) => ltrim($type, "\\"), $types);
                    if(in_array("EndOfRequest", array_map(fn(string $name) => basename(str_replace("\\", "/", $name)), $names), true)) break;

                    if(!empty(array_intersect($names, self::TOO_BROAD))) {
                        $catches[] = [
                            "line" => $tokens[array_key_first($types)][2],
                            "from" => array_key_first($types),
                            "to" => array_key_last($types),
                            "names" => array_values($types),
                        ];
                        break;
                    }

                    $at = self::next($tokens, self::blockEnd($tokens, $at) + 1);
                }
            }

            return $catches;
        }

        // True when a call like $res->success( sits between the two braces
        private static function endsRequest(array $tokens, int $open, int $close): bool {
            for($i = $open; $i < $close; $i++) {
                if(!is_array($tokens[$i])) continue;
                if(!in_array($tokens[$i][0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) continue;

                $method = $tokens[$i + 1] ?? null;
                if(!is_array($method) || !in_array($method[1], self::ENDING_CALLS, true)) continue;
                if("(" !== ($tokens[$i + 2] ?? null)) continue;

                $receiver = $tokens[$i - 1];
                if(is_array($receiver) && in_array($receiver[1], self::RECEIVERS, true)) return true;

                // response()->... and request()->...
                $helper = $tokens[$i - 3] ?? null;
                if(")" === $receiver && is_array($helper) && in_array($helper[1], self::RECEIVER_HELPERS, true)) return true;
            }

            return false;
        }

        // Index of the next token that is no whitespace and no comment
        private static function next(array $tokens, int $i): int {
            while(is_array($tokens[$i] ?? null) && in_array($tokens[$i][0], self::INSIGNIFICANT, true)) $i++;
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
