<?php

    use ZubZet\Framework\Support\StaticCache;
    use ZubZet\Framework\Support\Checkpoint\CanCheckpoint;
    use ZubZet\Framework\Support\Checkpoint\Checkpointable;

    // Probes for defensive branches in src/Support/* that aren't reachable
    // from any natural request flow. Covered by
    // tests/cypress/e2e/support/internals.cy.js. No DB access - no seed.
    class SupportProbeController extends z_controller {

        // StaticCache::get throws when the *type* bucket has never been set.
        public function action_staticCacheMissingType(Request $req, Response $res) {
            $message = null;
            try {
                StaticCache::get("__never_set_type__", "any-key");
            } catch (\InvalidArgumentException $e) {
                $message = $e->getMessage();
            }
            echo json_encode(["message" => $message]);
        }

        // StaticCache::get throws when the key is missing under an existing type.
        // Populate the type so the second branch fires (key missing under an
        // existing type, not the type-missing branch).
        public function action_staticCacheMissingKey(Request $req, Response $res) {
            $message = null;
            try {
                StaticCache::set("probe_supportcache", "exists", "value");
                StaticCache::get("probe_supportcache", "__never_set_key__");
            } catch (\InvalidArgumentException $e) {
                $message = $e->getMessage();
            }
            echo json_encode(["message" => $message]);
        }

        // HasDynamicAttributes::__get refuses direct reads of the internal store.
        public function action_dynamicAttributesGetStore(Request $req, Response $res) {
            $message = null;
            try {
                zubzet()->dynamicAttributesStore;
            } catch (\InvalidArgumentException $e) {
                $message = $e->getMessage();
            }
            echo json_encode(["message" => $message]);
        }

        // HasDynamicAttributes::__isset refuses the same probe via isset().
        public function action_dynamicAttributesIssetStore(Request $req, Response $res) {
            $message = null;
            try {
                isset(zubzet()->dynamicAttributesStore);
            } catch (\InvalidArgumentException $e) {
                $message = $e->getMessage();
            }
            echo json_encode(["message" => $message]);
        }

        // HasDynamicAttributes::__get throws when the attribute name was never set.
        public function action_dynamicAttributesMissing(Request $req, Response $res) {
            $message = null;
            try {
                zubzet()->some_attribute_that_was_never_set;
            } catch (\InvalidArgumentException $e) {
                $message = $e->getMessage();
            }
            echo json_encode(["message" => $message]);
        }

        // Drives Checkpoint::__construct + restore() through a mixed snapshot:
        // one initialized property, one uninitialized. After mutating both and
        // restoring, the initialized one is back to its captured value, and
        // the uninitialized one has been unset again. Uses an anonymous class
        // so no separate autoloadable test class is needed.
        public function action_checkpointRestore(Request $req, Response $res) {
            $target = new class implements Checkpointable {
                use CanCheckpoint;
                public string $initializedProp;
                public string $uninitializedProp;
            };

            $target->initializedProp = "initial-value";
            // uninitializedProp deliberately left unset for the snapshot.

            $checkpoint = $target->checkpointCurrentState(
                ["initializedProp", "uninitializedProp"],
            );

            // Mutate both - the restore must reverse this.
            $target->initializedProp = "changed";
            $target->uninitializedProp = "newly-set";

            $checkpoint->restore();

            echo json_encode([
                "initializedAfter"      => $target->initializedProp,
                // isset is false for typed-unset properties - exactly what
                // we want to assert: the unset($this->target->$name) branch ran.
                "uninitializedSetAfter" => isset($target->uninitializedProp),
            ]);
        }
    }
