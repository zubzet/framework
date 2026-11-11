<?php

    namespace Tests\Storage;

    use Tests\TestCase;
    use ZubZet\Framework\Database\DatabaseObject;
    use ZubZet\Framework\Storage\File;
    use ZubZet\Framework\Support\RuntimeCache\InternalRuntimeCache;

    class FileTest extends TestCase {

        protected function setUp(): void {
            $this->bootFramework();
        }

        // Construction

        public function testIsADatabaseObject(): void {
            $this->assertInstanceOf(DatabaseObject::class, $this->file());
        }

        public function testHoldsTheRowItWasBuiltFrom(): void {
            $row = $this->row();

            $this->assertSame($row, $this->file($row)->getAll());
        }

        // Getters

        public function testReadsTheIdAndTheUuid(): void {
            $file = $this->file(["id" => 7, "uuid" => "0b6e6a64-0000-4000-8000-000000000007"]);

            $this->assertSame(7, $file->id());
            $this->assertSame("0b6e6a64-0000-4000-8000-000000000007", $file->uuid());
        }

        public function testReadsTheDescribingFields(): void {
            $file = $this->file($this->row());

            $this->assertSame("uploads/report.pdf", $file->reference());
            $this->assertSame("report", $file->name());
            $this->assertSame("application/pdf", $file->type());
            $this->assertSame("pdf", $file->extension());
            $this->assertSame(2048, $file->size());
            $this->assertSame("2026-11-11 12:00:00", $file->created());
        }

        public function testReadsASizeBeyondThirtyTwoBits(): void {
            $file = $this->file(["size" => 5000000000]);

            $this->assertSame(5000000000, $file->size());
        }

        public function testReadsEveryFieldByItsName(): void {
            $this->assertSame("report", $this->file()->getField("name"));
        }

        public function testThrowsOnAnUnknownField(): void {
            $this->assertThrows(\InvalidArgumentException::class, "Field 'missing' does not exist", fn() => $this->file()->getField("missing"));
        }

        // Replacement

        public function testHasNoReplacementWithoutReplacedBy(): void {
            $this->assertNull($this->file(["replaced_by" => null])->replacedBy());
        }

        public function testLoadsTheReplacementById(): void {
            $this->models([8 => $this->row(["id" => 8, "reference" => "uploads/new.pdf"])]);

            $replacement = $this->file(["replaced_by" => 8])->replacedBy();

            $this->assertInstanceOf(File::class, $replacement);
            $this->assertSame(8, $replacement->id());
            $this->assertSame("uploads/new.pdf", $replacement->reference());
        }

        public function testRecordsAReplacement(): void {
            $models = $this->models([]);
            $file = $this->file(["id" => 1]);

            $file->replaceWith($this->file(["id" => 2]));

            $this->assertSame([[1, 2]], $models->file->replacements);
            $this->assertSame(2, $file->getField("replaced_by"));
        }

        // Adding

        public function testAddsAFileAndReturnsItsRow(): void {
            $models = $this->models([5 => $this->row(["id" => 5])]);
            $models->file->nextId = 5;

            $file = File::add("uploads/report.pdf", "report", "application/pdf", "pdf", 2048);

            $this->assertSame(5, $file->id());
            $this->assertSame([["uploads/report.pdf", "application/pdf", "report", "pdf", 2048]], $models->file->added);
        }

        public function testAddsAFileWithOnlyAReference(): void {
            $models = $this->models([5 => $this->row(["id" => 5])]);
            $models->file->nextId = 5;

            File::add("uploads/report.pdf");

            $this->assertSame([["uploads/report.pdf", "", "", "", 0]], $models->file->added);
        }

        // Retrieval

        public function testFindsAFileByItsReference(): void {
            $this->models([3 => $this->row(["id" => 3])]);

            $file = File::byReference("uploads/report.pdf");

            $this->assertSame(3, $file->id());
        }

        public function testFindsNoFileForAnUnknownReference(): void {
            $this->models([]);

            $this->assertNull(File::byReference("uploads/missing.pdf"));
        }

        // Removing

        public function testRemovesAFileAndDetachesTheInstance(): void {
            $models = $this->models([]);
            $file = $this->file(["id" => 4]);

            $file->remove();

            $this->assertSame([4], $models->file->removed);
            $this->assertThrows(\InvalidArgumentException::class, "Instance no longer exists", fn() => $file->id());
        }

        // Detached instance

        public function testRefusesEveryReadOnceDetached(): void {
            $file = $this->file();
            $file->nullId();

            $this->assertThrows(\InvalidArgumentException::class, "Instance no longer exists", fn() => $file->id());
            $this->assertThrows(\InvalidArgumentException::class, "Instance no longer exists", fn() => $file->getField("name"));
            $this->assertThrows(\InvalidArgumentException::class, "Instance no longer exists", fn() => $file->name());
            $this->assertThrows(\InvalidArgumentException::class, "Instance no longer exists", fn() => $file->getAll());
        }

        // Helpers

        /** @param array<string, mixed> $overrides */
        private function row(array $overrides = []): array {
            return array_merge([
                "id" => 1,
                "uuid" => "0b6e6a64-0000-4000-8000-000000000001",
                "reference" => "uploads/report.pdf",
                "name" => "report",
                "type" => "application/pdf",
                "extension" => "pdf",
                "size" => 2048,
                "created" => "2026-11-11 12:00:00",
                "replaced_by" => null,
                "active" => 1,
            ], $overrides);
        }

        /** @param array<string, mixed> $overrides */
        private function file(array $overrides = []): File {
            return new File($this->row($overrides));
        }

        /**
         * Puts stand-ins for the z_file and z_permission models in the model
         * cache, so a File reads its rows from the given list instead of a
         * database. The model lookup finds them without a file on disk.
         *
         * @param array<int, array> $rows The z_file rows by id
         */
        private function models(array $rows): object {
            $file = new class {
                public array $added = [];
                public array $replacements = [];
                public array $removed = [];
                public int $nextId = 1;

                public function add($reference, $type, $name, $extension, $size): int {
                    $this->added[] = [$reference, $type, $name, $extension, $size];
                    return $this->nextId;
                }

                public function replaceWith(File $file, File $replacement): void {
                    $this->replacements[] = [$file->id(), $replacement->id()];
                }

                public function remove(File $file): void {
                    $this->removed[] = $file->id();
                }
            };

            $permission = new class($rows) {
                public function __construct(private array $rows) {}

                public function getByIdentifier(string $table, string $column, int|string $identifier, ?array $expression = null): ?array {
                    foreach($this->rows as $row) {
                        if($row[$column] == $identifier) return $row;
                    }
                    return null;
                }
            };

            // getModel() asks the registry for the file, then the cache for the instance
            foreach(["z_file" => $file, "z_permission" => $permission] as $name => $model) {
                InternalRuntimeCache::set("registry_find", "models:{$name}Model", "/stub/{$name}Model.php");
                InternalRuntimeCache::set("model", "/stub/{$name}Model.php", $model);
            }

            return (object) ["file" => $file];
        }
    }

?>
