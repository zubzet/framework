<?php

    /**
     * Helper function to get the caller of a function
     * @param int $depth Index of the callstack from back to front
     * @return any The caller
     */
    function getCaller($depth = 1) {
        return debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3)[$depth + 1]['function'];
    }

    function e(?string $value): ?string {
        if(is_null($value)) return null;
        $value = strip_tags($value);
        return htmlspecialchars($value);
    }

    function makeSlug($str) {
        $str = mb_strtolower($str);
        $str = preg_replace("/[^A-Za-z0-9-_ ]/", '', $str);
        $str = str_replace("_", "-", $str);
        $str = str_replace(" ", "-", $str);
        $result = $str;
        do {
            $str = $result;
            $result = str_replace("--", "-", $str);
        } while($result != $str);
        return trim($result, "-_");
    }

    function uecho($value) {
        $value = strip_tags($value);
        echo htmlspecialchars($value);
    }

    function var_swap(&$x, &$y) {
        $tmp = $x;
        $x = $y;
        $y = $tmp;
    }

    function shortenStr($str, $maxlength = 10, $cutDescriptor = "...") {
        $maxlength -= mb_strlen($cutDescriptor);
        $result = "";
        foreach(explode(" ", $str) as $part) {
            $lengthLeft = $maxlength - mb_strlen($result);
            if($lengthLeft > mb_strlen($part)) {
                $result .= "$part ";
            } else {
                $result .= mb_substr($part, 0, $lengthLeft);
                $result .= $cutDescriptor;
                break;
            }
        }
        return $result;
    }

    function emptyToNull(&$value) {
        if(empty($value) || $value === "null") {
            $value = null;
        }
    }

    function de_strtolower($string) {
        return str_replace([
            "Ä", "Ö", "Ü", "ß"
        ], [
            "ä", "ö", "ü", "ss"
        ], strtolower($string));
    }

?>