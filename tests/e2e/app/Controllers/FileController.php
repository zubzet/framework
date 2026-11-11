<?php

    use ZubZet\Framework\Storage\File;

    class FileController extends z_controller {

        /**
         *
         * @var File Getters
         *
         */
        public function action_byId(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byId(500))));
        }

        public function action_byIdInactive(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byId(501))));
        }

        public function action_byIds(Request $req, Response $res): void {
            echo(json_encode(array_map(fn($file) => $this->getFile($file), File::byIds(500, 501, 502))));
        }

        public function action_byUuids(Request $req, Response $res): void {
            $files = File::byUuids(
                "01890000-0000-7000-8000-000000000500",
                "01890000-0000-7000-8000-000000000501",
                "01890000-0000-7000-8000-000000000502",
            );

            echo(json_encode(array_map(fn($file) => $this->getFile($file), $files)));
        }

        public function action_all(Request $req, Response $res): void {
            echo(json_encode(array_map(fn($file) => $file->id(), File::all())));
        }

        public function action_getters(Request $req, Response $res): void {
            $file = File::byId(500);

            echo(json_encode([
                "uuid" => $file->uuid(),
                "created" => $file->created(),
            ]));
        }

        public function action_byUuid(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byUuid("01890000-0000-7000-8000-000000000500"))));
        }

        public function action_byReference(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byReference("file_byReference"))));
        }

        public function action_byReferenceInactive(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byReference("file_byId_Inactive"))));
        }


        /**
         *
         * @var File Interactions
         *
         */
        public function action_add(Request $req, Response $res): void {
            $file = File::add("file_add_New", "added.pdf", "application/pdf", "pdf", 4096);

            echo(json_encode([
                "createdFileDirect" => $this->getFile($file),
                "createdFileGet" => $this->getFile(File::byId($file->id())),
            ]));
        }

        public function action_addDefaults(Request $req, Response $res): void {
            $file = File::add("file_add_Defaults");
            echo(json_encode($this->getFile(File::byId($file->id()))));
        }

        public function action_update(Request $req, Response $res): void {
            $file = File::byId(515);
            $file->update("file_update_New", "updated.pdf", "application/pdf", "pdf", 4096);

            $cleared = $file->getAll() === [];
            $file->refresh();

            echo(json_encode([
                "cleared" => $cleared,
                "refreshed" => $this->getFile($file),
                "reloaded" => $this->getFile(File::byId(515)),
            ]));
        }

        public function action_updateDefaults(Request $req, Response $res): void {
            File::byId(516)->update("file_update_DefaultsNew");
            echo(json_encode($this->getFile(File::byId(516))));
        }

        public function action_remove(Request $req, Response $res): void {
            File::byId(503)->remove();

            // The row stays, only deactivated
            $row = model("z_file")->getById(503);

            echo(json_encode([
                "file" => $this->getFile(File::byId(503)),
                "rowKept" => !is_null($row),
                "active" => (int) $row["active"],
            ]));
        }

        public function action_removeDetaches(Request $req, Response $res): void {
            $file = File::add("file_remove_Detaches");
            $file->remove();

            echo(json_encode([
                "id" => $this->throwsDetached(fn() => $file->id()),
                "removeAgain" => $this->throwsDetached(fn() => $file->remove()),
                "refresh" => $this->throwsDetached(fn() => $file->refresh()),
                "update" => $this->throwsDetached(fn() => $file->update("file_remove_Update")),
                "replaceWith" => $this->throwsDetached(fn() => $file->replaceWith(File::byId(500))),
                "replaceWithDetached" => $this->throwsDetached(fn() => File::byId(500)->replaceWith($file)),
                "otherUntouched" => File::byId(500)->replacedBy()?->id(),
            ]));
        }

        public function action_refresh(Request $req, Response $res): void {
            $file = File::byId(510);
            File::byId(510)->replaceWith(File::byId(511));

            $beforeRefresh = $file->replacedBy()?->id();
            $file->refresh();

            echo(json_encode([
                "beforeRefresh" => $beforeRefresh,
                "afterRefresh" => $file->replacedBy()?->id(),
            ]));
        }

        public function action_replaceWith(Request $req, Response $res): void {
            $file = File::byId(504);
            $file->replaceWith(File::byId(505));

            echo(json_encode([
                "direct" => $file->replacedBy()?->id(),
                "reloaded" => File::byId(504)->replacedBy()?->id(),
                "replacementUntouched" => File::byId(505)->replacedBy()?->id(),
            ]));
        }

        public function action_replaceWithOverwrite(Request $req, Response $res): void {
            File::byId(512)->replaceWith(File::byId(514));
            echo(json_encode($this->getFile(File::byId(512)->replacedBy())));
        }

        public function action_replacedBy(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byId(506)->replacedBy())));
        }

        public function action_replacedByNone(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byId(500)->replacedBy())));
        }

        public function action_replacedByRemoved(Request $req, Response $res): void {
            echo(json_encode($this->getFile(File::byId(508)->replacedBy())));
        }

        public function action_upload(Request $req, Response $res): void {
            $upload = $res->upload();
            $code = $upload->upload($req->getFile("file"), "uploads/", 0, ["txt"]);
            $file = File::byId($upload->fileId);

            echo(json_encode([
                "code" => $code,
                "sameId" => $file->id() === $upload->fileId,
                "sameReference" => $file->reference() === $upload->ref,
                "byReference" => File::byReference($upload->ref)?->id() === $upload->fileId,
                "name" => $file->name(),
                "type" => $file->type(),
                "extension" => $file->extension(),
                "size" => $file->size(),
            ]));
        }

        private function throwsDetached(callable $call): bool {
            try {
                $call();
                return false;
            } catch(\InvalidArgumentException $e) {
                return $e->getMessage() === "Instance no longer exists";
            }
        }

        private function getFile(?File $file): array {
            if(is_null($file)) return ["found" => false];

            return [
                "id" => $file->id(),
                "reference" => $file->reference(),
                "name" => $file->name(),
                "type" => $file->type(),
                "extension" => $file->extension(),
                "size" => $file->size(),
            ];
        }
    }

?>
