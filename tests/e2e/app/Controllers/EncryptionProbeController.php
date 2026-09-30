<?php

    use ZubZet\Framework\Security\Encryption;

    class EncryptionProbeController extends z_controller {

        private const SECRET = "s3-secret-key/with+special=chars äöü";

        public function action_roundTrip(Request $req, Response $res) {
            $encrypted = encryptSecret(self::SECRET);
            return $res->json([
                "prefixed" => str_starts_with($encrypted, "zenc1:"),
                "containsPlaintext" => str_contains($encrypted, self::SECRET),
                "matches" => self::SECRET === decryptSecret($encrypted),
            ]);
        }

        public function action_roundTripEmpty(Request $req, Response $res) {
            return $res->json(["matches" => "" === Encryption::decrypt(Encryption::encrypt(""))]);
        }

        public function action_randomIv(Request $req, Response $res) {
            return $res->json(["differs" => encryptSecret(self::SECRET) !== encryptSecret(self::SECRET)]);
        }

        public function action_wrongKey(Request $req, Response $res) {
            $encrypted = encryptSecret(self::SECRET);
            return $this->withKey(str_repeat("w", 32), fn() => decryptSecret($encrypted));
        }

        public function action_tampered(Request $req, Response $res) {
            $payload = base64_decode(substr(encryptSecret(self::SECRET), strlen("zenc1:")));
            $payload[strlen($payload) - 1] = chr(ord($payload[strlen($payload) - 1]) ^ 1);
            return $this->catchThrowableMessage(fn() => decryptSecret("zenc1:" . base64_encode($payload)));
        }

        public function action_unknownFormat(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => decryptSecret("plaintext"));
        }

        public function action_malformed(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => decryptSecret("zenc1:" . base64_encode("short")));
        }

        public function action_invalidBase64(Request $req, Response $res) {
            return $this->catchThrowableMessage(fn() => decryptSecret("zenc1:!!!"));
        }

        public function action_shortKey(Request $req, Response $res) {
            return $this->withKey("too-short", fn() => encryptSecret(self::SECRET));
        }

        public function action_missingKey(Request $req, Response $res) {
            return $this->withKey("", fn() => encryptSecret(self::SECRET));
        }

        private function withKey(string $key, \Closure $action): void {
            $original = zubzet()->{Encryption::SETTING};
            zubzet()->{Encryption::SETTING} = $key;

            try {
                $this->catchThrowableMessage($action);
            } finally {
                zubzet()->{Encryption::SETTING} = $original;
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
