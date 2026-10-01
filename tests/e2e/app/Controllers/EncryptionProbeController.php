<?php

    use ZubZet\Framework\Security\Encryption;

    class EncryptionProbeController extends z_controller {

        private const SECRET = "s3-secret-key/with+special=chars äöü";
        private const HEADER = "zenc:aes-256-gcm:";

        public function action_roundTrip(Request $req, Response $res) {
            $encrypted = encryptSecret(self::SECRET);
            return $res->json([
                "encrypted" => $encrypted,
                "containsPlaintext" => str_contains($encrypted, self::SECRET),
                "matches" => self::SECRET === decryptSecret($encrypted),
            ]);
        }

        public function action_roundTripEmpty(Request $req, Response $res) {
            return $res->json(["matches" => "" === Encryption::decrypt(Encryption::encrypt(""))]);
        }

        public function action_roundTripAllBytes(Request $req, Response $res) {
            $bytes = implode("", array_map("chr", range(0, 255)));
            return $res->json(["matches" => $bytes === decryptSecret(encryptSecret($bytes))]);
        }

        public function action_roundTripJson(Request $req, Response $res) {
            $data = [
                "accessKey" => "AKIAEXAMPLE",
                "secretKey" => "quote\" backslash\\ newline\n slash/ plus+ equals= äöü 🔑",
                "applicationId" => 42,
                "options" => ["region" => null, "pathStyle" => true],
            ];
            $decoded = json_decode(decryptSecret(encryptSecret(json_encode($data))), true);
            return $res->json(["matches" => $data === $decoded]);
        }

        public function action_randomIv(Request $req, Response $res) {
            return $res->json(["differs" => encryptSecret(self::SECRET) !== encryptSecret(self::SECRET)]);
        }

        public function action_wrongKey(Request $req, Response $res) {
            $encrypted = encryptSecret(self::SECRET);
            return $this->withKey(str_repeat("w", 32), fn() => decryptSecret($encrypted));
        }

        public function action_tampered(Request $req, Response $res) {
            $payload = base64_decode(strtr(substr(encryptSecret(self::SECRET), strlen(self::HEADER)), "-_", "+/"));
            $payload[strlen($payload) - 1] = chr(ord($payload[strlen($payload) - 1]) ^ 1);
            $tampered = self::HEADER . rtrim(strtr(base64_encode($payload), "+/", "-_"), "=");
            return $this->catchThrowableMessage(fn() => decryptSecret($tampered));
        }

        public function action_unknownFormat(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => decryptSecret("plaintext"));
        }

        public function action_unknownCipher(Request $req, Response $res) {
            $relabeled = str_replace(self::HEADER, "zenc:rot13:", encryptSecret(self::SECRET));
            return $this->catchThrowableMessage(fn() => decryptSecret($relabeled));
        }

        public function action_malformed(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => decryptSecret(self::HEADER . "c2hvcnQ"));
        }

        public function action_invalidCharacters(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => decryptSecret(self::HEADER . "ab+/"));
        }

        public function action_invalidLength(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => decryptSecret(self::HEADER . "abcde"));
        }

        public function action_shortKey(Request $req, Response $res) {
            return $this->withKey("too-short", fn() => encryptSecret(self::SECRET));
        }

        public function action_missingKey(Request $req, Response $res) {
            return $this->withKey(null, fn() => encryptSecret(self::SECRET));
        }

        public function action_missingKeyOnDecrypt(Request $req, Response $res) {
            $encrypted = encryptSecret(self::SECRET);
            return $this->withKey(null, fn() => decryptSecret($encrypted));
        }

        /** Runs $action with the key replaced, or removed from the settings when $key is null. */
        private function withKey(?string $key, \Closure $action): void {
            $original = zubzet()->getAllAttributes();

            $settings = $original;
            unset($settings[Encryption::SETTING]);
            if(null !== $key) {
                $settings[Encryption::SETTING] = $key;
            }
            zubzet()->setAttributes($settings);

            try {
                $this->catchThrowableMessage($action);
            } finally {
                zubzet()->setAttributes($original);
            }
        }

        private function catchThrowableMessage(\Closure $action): void {
            try {
                $action();
                response()->json(["threw" => false]);
            } catch (\Throwable $e) {
                response()->json([
                    "threw" => true,
                    "type" => get_class($e),
                    "message" => $e->getMessage(),
                ]);
            }
        }
    }
?>
