<?php

    namespace Tests\IncludedComponents\models;

    use InvalidArgumentException;
    use ReflectionClass;
    use Tests\TestCase;
    use z_migrationModel;

    require_once __DIR__ . "/../../../../../src/IncludedComponents/models/z_migrationModel.php";

    class z_migrationModelTest extends TestCase {

        // The Migration-File names

        /** @dataProvider invalidNames */
        public function testChecksTheMigrationFileNamesCorrectly(string $file, string $expectedError): void {
            $this->assertThrows(InvalidArgumentException::class, $expectedError, fn() => $this->sort([$file]));
        }

        public static function invalidNames(): array {
            return [
                "Syntax.sql" => [
                    "Syntax.sql",
                    "Formatting error: 'Syntax.sql'. Expected: YYYY-MM-DD_Name",
                ],
                "2025-10-10.sql" => [
                    "2025-10-10.sql",
                    "Formatting error: '2025-10-10.sql'. Expected: YYYY-MM-DD_Name",
                ],
                "12-10-2004_Wrong_Date.sql" => [
                    "12-10-2004_Wrong_Date.sql",
                    "Syntax error: '12-10-2004_Wrong_Date.sql'. Date must be exactly YYYY-MM-DD.",
                ],
                "1990-10-10_Test_Name.sql" => [
                    "1990-10-10_Test_Name.sql",
                    "History error: The year 1990 is too far in the past.",
                ],
                "a date followed by more text" => [
                    "2025-10-10x_Name.sql",
                    "Syntax error: '2025-10-10x_Name.sql'. Date must be exactly YYYY-MM-DD.",
                ],
                "the last year before 2000" => [
                    "1999-12-31_Name.sql",
                    "History error: The year 1999 is too far in the past.",
                ],
                "a version without a name" => [
                    "2025-10-10_1_",
                    "Name error: No name found in '2025-10-10_1_'.",
                ],
            ];
        }

        public function testNamesTheFileWithoutItsDirectoryInTheError(): void {
            $this->assertThrows(
                InvalidArgumentException::class,
                "Formatting error: 'Syntax.sql'. Expected: YYYY-MM-DD_Name",
                fn() => $this->sort(["app/Database/migrations/Syntax.sql"])
            );
        }

        // PHP rolls such a date over (February 30 becomes March 2) instead of refusing it
        /** @dataProvider impossibleDates */
        public function testRejectsADateThatIsNotInTheCalendar(string $file, string $date): void {
            $this->assertThrows(
                InvalidArgumentException::class,
                "Date error: '$file'. $date is not a date in the calendar.",
                fn() => $this->sort([$file]),
            );
        }

        public static function impossibleDates(): array {
            return [
                "February 30" => ["2025-02-30_Name.sql", "2025-02-30"],
                "month 13" => ["2025-13-01_Name.sql", "2025-13-01"],
                "day 0" => ["2025-10-00_Name.sql", "2025-10-00"],
                "February 29 outside a leap year" => ["2025-02-29_Name.sql", "2025-02-29"],
            ];
        }

        public function testAcceptsFebruary29InALeapYear(): void {
            $file = $this->sort(["2024-02-29_Name.sql"])[0];

            $this->assertSame("2024-02-29", $file->date->format("Y-m-d"));
        }

        public function testAcceptsTheFirstYearThatIsNotTooFarInThePast(): void {
            $file = $this->sort(["2000-01-01_Name.sql"])[0];

            $this->assertSame("2000-01-01", $file->date->format("Y-m-d"));
        }

        public function testAcceptsAFarFutureDate(): void {
            $file = $this->sort(["5000-10-10_Test_Name.sql"])[0];

            $this->assertSame("5000-10-10", $file->date->format("Y-m-d"));
            $this->assertSame("Test_Name.sql", $file->name);
        }

        public function testSplitsTheNameIntoDateVersionAndName(): void {
            $file = $this->sort(["app/2025-01-05_2_Syn_File.sql"])[0];

            $this->assertSame("app/2025-01-05_2_Syn_File.sql", $file->filename);
            $this->assertSame("2025-01-05 00:00:00", $file->date->format("Y-m-d H:i:s"));
            $this->assertSame("2", $file->version);
            $this->assertSame("Syn_File.sql", $file->name);
        }

        public function testAFileWithoutAVersionHasVersionZero(): void {
            $file = $this->sort(["2025-01-05_Syn_File.sql"])[0];

            $this->assertSame("0", $file->version);
            $this->assertSame("Syn_File.sql", $file->name);
        }

        // Date, then version, then name
        public function testSortsTheFilesByDateThenVersionThenName(): void {
            $files = $this->sort([
                "2005-01-10_2_A.sql",
                "2005-01-10_1_B.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_A.sql",
                "2005-01-01_2_Syn_File.sql",
            ]);

            $this->assertSame([
                "2005-01-01_2_Syn_File.sql",
                "2005-01-05_1_Syn_File.sql",
                "2005-01-10_1_A.sql",
                "2005-01-10_1_B.sql",
                "2005-01-10_2_A.sql",
            ], array_map(fn($file) => $file->filename, $files));
        }

        private function sort(array $files): array {
            $model = (new ReflectionClass(z_migrationModel::class))->newInstanceWithoutConstructor();
            return $model->sortMigrations($files);
        }
    }

?>
