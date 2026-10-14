<?php

    namespace ZubZet\Framework\Security\Secrets;

    /** Thrown when a value cannot be decrypted: a wrong key, or a malformed or modified value. */
    class DecryptionException extends \RuntimeException {}

?>
