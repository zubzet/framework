<?php
    /**
     * Stripped-down boot harness that exercises the framework's
     * "instance not (yet) set up" branches in src/Support/GlobalReferences.php.
     * Run directly via `php instance_test.php` - never serves an HTTP request.
     *
     * Emits a single JSON line covering every branch we want to cover, so the
     * companion cypress spec only needs one cy.exec.
     *
     * Example stdout (single line, pretty-printed here for readability):
     *
     *   {
     *     "zubzetNotInstantiated": "The requested instance 'ZubZet (The framework itself)' has not yet been setup.",
     *     "nonDefault":            "Only the default connection is supported so far.",
     *     "allowedNullWhenUnset":  true,
     *     "strictWhenUnset":       "The requested instance 'Connection (Database)' has not yet been setup."
     *   }
     */

    use ZubZet\Framework\ZubZet;
    use ZubZet\Framework\Support\GlobalReferences;
    use ZubZet\Framework\ErrorHandling\GenericException\NotInstantiatedException;

    chdir(realpath(__DIR__));

    $source = getenv("COMPOSER_VENDOR_DIR") ?: "./";
    require_once "$source/autoload.php";

    // Triggers the autoload of src/Support/GlobalReferences.php so the global
    // helper functions (zubzet/db/etc.) get declared. Normally this happens
    // inside ZubZet::__construct *after* self::$instance is set - but we need
    // the function defined *before* instantiation so we can reach the
    // "not yet been setup" branch below.
    new GlobalReferences;

    $results = [];

    // 1. zubzet() called before ZubZet::$instance is ever assigned. Only this
    //    file's controlled boot can reach this branch - once any request boots
    //    the framework, $instance is permanently set for that process.
    try {
        zubzet();
        $results["zubzetNotInstantiated"] = null;
    } catch (NotInstantiatedException $e) {
        $results["zubzetNotInstantiated"] = $e->getMessage();
    }

    // Boot the framework so the remaining db() branches can run. We do *not*
    // call execute() - that would handle a (non-existent) HTTP request.
    new ZubZet();

    // 2. db() with a non-default connection key throws InvalidArgumentException.
    try {
        db("custom-not-default");
        $results["nonDefault"] = null;
    } catch (\InvalidArgumentException $e) {
        $results["nonDefault"] = $e->getMessage();
    }

    // 3. After unsetting z_db, db("default", allowUnsetConnection: true) hits
    //    the !isset early-return and returns null.
    unset(zubzet()->z_db);
    $results["allowedNullWhenUnset"] = db("default", true) === null;

    // 4. db() (strict, allowUnsetConnection=false) needs z_db to be SET but
    //    not a Connection instance - that's the only path to the instanceof
    //    check. With z_db unset, the previous branch would throw __get's
    //    "attribute does not exist" before db() got that far. A stdClass
    //    satisfies isset() while failing the instanceof Connection guard.
    zubzet()->z_db = new \stdClass();
    try {
        db();
        $results["strictWhenUnset"] = null;
    } catch (NotInstantiatedException $e) {
        $results["strictWhenUnset"] = $e->getMessage();
    }

    echo json_encode($results);
?>
