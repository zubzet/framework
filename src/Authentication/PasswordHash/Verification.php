<?php

    namespace ZubZet\Framework\Authentication\PasswordHash;

    /**
     * The outcome of a {@see Password::verify()} call; read it via {@see isCorrect()}.
     *
     * When the password is correct but its stored hash is stale, it keeps the
     * plaintext so the fresh native hash can be produced on demand (rehash-on-login).
     * Hashing is deferred to {@see upgradePassword()}, so a plain isCorrect() check
     * never pays for it. Immutable; obtained only from the verifier.
     */
    final class Verification {

        private bool $correct;
        private ?string $upgradePlaintext;

        private function __construct(bool $correct, ?string $upgradePlaintext) {
            $this->correct = $correct;
            $this->upgradePlaintext = $upgradePlaintext;
        }

        /** Whether the supplied password was correct. */
        public function isCorrect(): bool {
            return $this->correct;
        }

        /** Whether a fresh hash should be persisted after this (correct) verify. */
        public function isUpgradeNeeded(): bool {
            return !is_null($this->upgradePlaintext);
        }

        /**
         * The fresh native hash to persist. Run after isUpgradeNeeded()
         * @throws \LogicException when no upgrade is pending.
         */
        public function upgradePassword(): string {
            if(is_null($this->upgradePlaintext)) {
                throw new \LogicException("No upgrade pending; guard with isUpgradeNeeded() before calling upgradePassword().");
            }
            return Password::hash($this->upgradePlaintext);
        }

        /** @internal The password was wrong. */
        public static function createWrong(): self {
            return new self(false, null);
        }

        /** @internal Correct, and the stored hash is already current. */
        public static function createCorrect(): self {
            return new self(true, null);
        }

        /** @internal Correct, but stale; keeps `$password` to rehash on demand. */
        public static function createCorrectWithUpgrade(string $password): self {
            return new self(true, $password);
        }
    }
?>
