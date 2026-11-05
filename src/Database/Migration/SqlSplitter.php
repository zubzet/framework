<?php
    namespace ZubZet\Framework\Database\Migration;

    // Splits a SQL script into single statements without breaking on semicolons
    // inside string literals, quoted identifiers or comments
    class SqlSplitter {

        public static function split(string $sql): array {
            $statements = [];
            $current = "";
            $delimiter = ";";
            $length = strlen($sql);
            $i = 0;

            while($i < $length) {
                $char = $sql[$i];

                // "DELIMITER <token>" on its own line changes the terminator, as in the mysql client
                if(($char === "d" || $char === "D" || ctype_space($char)) && trim($current) === "" && preg_match('/\G[ \t]*DELIMITER[ \t]+(\S+)[^\S\n]*(?:\n|$)/Ai', $sql, $match, 0, $i)) {
                    $delimiter = $match[1];
                    $i += strlen($match[0]);
                    continue;
                }

                // String literal or quoted identifier: copied verbatim, including both quotes
                if($char === "'" || $char === '"' || $char === "`") {
                    $end = $i + 1;
                    while($end < $length) {
                        // A backslash escapes the next character, but not inside `identifiers`
                        if($sql[$end] === "\\" && $char !== "`") {
                            $end += 2;
                            continue;
                        }

                        if($sql[$end] === $char) {
                            // A doubled quote is an escaped quote and does not close the literal
                            if(($sql[$end + 1] ?? "") === $char) {
                                $end += 2;
                                continue;
                            }
                            break;
                        }

                        $end++;
                    }

                    // An unterminated literal runs to the end of the script
                    $end = min($end + 1, $length);
                    $current .= substr($sql, $i, $end - $i);
                    $i = $end;
                    continue;
                }

                // Line comment: "#" or "-- " (MySQL needs whitespace or the end of the script after the dashes)
                if($char === "#" || ($char === "-" && preg_match('/\G--(?:\s|$)/A', $sql, $match, 0, $i))) {
                    $lineEnd = strpos($sql, "\n", $i);
                    $i = ($lineEnd === false) ? $length : $lineEnd;
                    continue;
                }

                // Block comment: dropped, except executable ones (/*! ... */) and optimizer hints (/*+ ... */)
                if(substr($sql, $i, 2) === "/*") {
                    $closing = strpos($sql, "*/", $i + 2);
                    $end = ($closing === false) ? $length : $closing + 2;

                    // A dropped comment still separates the tokens around it
                    $keep = in_array($sql[$i + 2] ?? "", ["!", "+"], true);
                    $current .= $keep ? substr($sql, $i, $end - $i) : " ";
                    $i = $end;
                    continue;
                }

                if(substr($sql, $i, strlen($delimiter)) === $delimiter) {
                    self::addStatement($statements, $current);
                    $i += strlen($delimiter);
                    continue;
                }

                $current .= $char;
                $i++;
            }

            self::addStatement($statements, $current);

            return $statements;
        }

        // Moves the collected text into the statement list, ignoring empty ones
        private static function addStatement(array &$statements, string &$current): void {
            $statement = trim($current);
            if($statement !== "") $statements[] = $statement;

            $current = "";
        }
    }
