<?php

    namespace Tests\Database\Migration;

    use Tests\TestCase;
    use ZubZet\Framework\Database\Migration\SqlSplitter;

    class SqlSplitterTest extends TestCase {

        private const SEMICOLONS = <<<'SQL'
            -- A comment line; its semicolon does not end a statement
            # A hash comment; with a semicolon
            CREATE TABLE `migration_semicolons` (
                `id` INT PRIMARY KEY AUTO_INCREMENT,
                -- An indented comment; inside the statement
                `value` VARCHAR(255) NOT NULL
            );

            INSERT INTO `migration_semicolons` (`value`) VALUES ('first');
            -- A comment; between two statements
            INSERT INTO `migration_semicolons` (`value`) VALUES ('second');
            -- A trailing comment; after the last statement

            INSERT INTO `migration_semicolons` (`value`) VALUES ('third; in a string'); -- trailing; comment
            INSERT INTO `migration_semicolons` (`value`) VALUES ("fourth; \"quoted\"; and 'it''s'") /* block; comment */;
            /* A block comment;
               over several lines; */
            INSERT INTO `migration_semicolons` (`value`) VALUES ('fifth; it''s; escaped\'; still one');
            SQL;

        // Semicolons

        public function testDoesNotSplitAStatementAtASemicolonInACommentOrString(): void {
            $this->assertSame([
                "CREATE TABLE `migration_semicolons` (\n    `id` INT PRIMARY KEY AUTO_INCREMENT,\n    \n    `value` VARCHAR(255) NOT NULL\n)",
                "INSERT INTO `migration_semicolons` (`value`) VALUES ('first')",
                "INSERT INTO `migration_semicolons` (`value`) VALUES ('second')",
                "INSERT INTO `migration_semicolons` (`value`) VALUES ('third; in a string')",
                "INSERT INTO `migration_semicolons` (`value`) VALUES (\"fourth; \\\"quoted\\\"; and 'it''s'\")",
                "INSERT INTO `migration_semicolons` (`value`) VALUES ('fifth; it''s; escaped\\'; still one')",
            ], SqlSplitter::split(self::SEMICOLONS));
        }

        public function testKeepsTheLastStatementWithoutATrailingSemicolon(): void {
            $this->assertSame(["SELECT 1", "SELECT 2"], SqlSplitter::split("SELECT 1; SELECT 2"));
        }

        public function testReturnsNoStatementsForAScriptOfOnlyCommentsAndSemicolons(): void {
            $this->assertSame([], SqlSplitter::split("-- nothing;\n;; /* here */ ;\n"));
            $this->assertSame([], SqlSplitter::split(""));
        }

        // Strings and identifiers

        public function testABackslashDoesNotEscapeInsideABacktickIdentifier(): void {
            $this->assertSame(["SELECT `a\\`", "SELECT 2"], SqlSplitter::split("SELECT `a\\`; SELECT 2;"));
        }

        public function testAnUnterminatedLiteralRunsToTheEndOfTheScript(): void {
            $this->assertSame(["SELECT 'a; SELECT 2;"], SqlSplitter::split("SELECT 'a; SELECT 2;"));
        }

        // Comments

        public function testTwoDashesWithoutWhitespaceAreNoComment(): void {
            $this->assertSame(["SELECT 1--1", "SELECT 2"], SqlSplitter::split("SELECT 1--1; SELECT 2;"));
        }

        public function testKeepsExecutableCommentsAndOptimizerHints(): void {
            $this->assertSame(
                ["/*!40101 SET NAMES utf8 */", "SELECT /*+ NO_RANGE_OPTIMIZATION(t) */ 1"],
                SqlSplitter::split("/*!40101 SET NAMES utf8 */; SELECT /*+ NO_RANGE_OPTIMIZATION(t) */ 1;")
            );
        }

        public function testADroppedBlockCommentStillSeparatesTheTokensAroundIt(): void {
            $this->assertSame(["SELECT 1"], SqlSplitter::split("SELECT/* x */1;"));
        }

        // Delimiter

        public function testDelimiterChangesTheTerminator(): void {
            $sql = "DELIMITER //\nCREATE PROCEDURE p() BEGIN SELECT 1; SELECT 2; END//\nDELIMITER ;\nSELECT 3;";

            $this->assertSame(["CREATE PROCEDURE p() BEGIN SELECT 1; SELECT 2; END", "SELECT 3"], SqlSplitter::split($sql));
        }

        public function testSplitsWindowsLineEndingsLikeUnixOnes(): void {
            $this->assertSame(["SELECT 1", "SELECT 2"], SqlSplitter::split("-- c;\r\nSELECT 1;\r\nSELECT 2;\r\n"));
        }
    }

?>
