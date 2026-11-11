<?php

    namespace Tests\Authentication\Permission;

    use Tests\TestCase;
    use ZubZet\Framework\Authentication\Permission\User;

    class UserTest extends TestCase {

        private const VERIFIED = "2025-01-01 12:00:00";

        // Verification

        public function testChecksIfAUserIsVerifiedWhenVerifiedIsNull(): void {
            $user = $this->user(null);

            $this->assertFalse($user->isVerified("NOW"));
            $this->assertFalse($user->isVerified("2020-01-01 00:00:00"));
        }

        public function testChecksIfAUserIsVerifiedWhenVerifiedIsNotNull(): void {
            $user = $this->user(self::VERIFIED);

            $this->assertTrue($user->isVerified("NOW"));
            $this->assertFalse($user->isVerified("2020-01-01 00:00:00"));
            $this->assertTrue($user->isVerified("2030-01-01 00:00:00"));
        }

        public function testIsVerifiedByTheSecondTheDateIsReached(): void {
            $user = $this->user(self::VERIFIED);

            $this->assertFalse($user->isVerified("2025-01-01 11:59:59"));
            $this->assertTrue($user->isVerified(self::VERIFIED));
        }

        public function testIsNotVerifiedAtAnUnparsableTime(): void {
            $this->assertFalse($this->user(self::VERIFIED)->isVerified("not a time"));
        }

        // A stored value strtotime() cannot read used to compare as 0 and count as verified
        /** @dataProvider unreadableVerificationDates */
        public function testIsNotVerifiedWhenTheStoredDateCannotBeRead(string $verified): void {
            $user = $this->user($verified);

            $this->assertFalse($user->isVerified("NOW"));
            $this->assertFalse($user->isVerified("2020-01-01 00:00:00"));
            $this->assertFalse($user->isVerified("2030-01-01 00:00:00"));
        }

        public static function unreadableVerificationDates(): array {
            return [
                "text that is no date" => ["not a date"],
                "an empty string" => [""],
            ];
        }

        private function user(?string $verified): User {
            return new User(["id" => 1, "verified" => $verified]);
        }
    }

?>
