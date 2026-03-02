<?php

    namespace ZubZet\Tooling\AutomatedChanges\V1_1_0;

    use DateTime;

    class MigrationNamingConventionChange {

        function validateMigrationName(string $fileName): bool {
            $segments = explode('_', $fileName);

            if(count($segments) < 2) return false;


            $dateString = $segments[0];
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) return false;

            $dateObj = DateTime::createFromFormat('Y-m-d', $dateString);
            if(!$dateObj) return false;

            $now = new DateTime('today');
            if($dateObj > $now) return false;

            $nameStartIndex = 1;

            if((int)$dateObj->format('Y') < 2000) return false;

            if(isset($segments[1]) && (filter_var($segments[1], FILTER_VALIDATE_INT))) {
                $nameStartIndex = 2;
            }

            $nameParts = array_slice($segments, $nameStartIndex);
            $name = implode('_', $nameParts);
            if(empty($name)) return false;;

            return true;
        }

        // This  is ai slop to at least try to give an automated change suggestion
        // The error mode is simply not having an automated change as the format
        // will be evaluated before suggesting it
        function renameAutomateChange(string $file): ?string {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $basename = pathinfo($file, PATHINFO_BASENAME);
            $nameNoExt = pathinfo($file, PATHINFO_FILENAME);
            $today = (new DateTime('today'))->format('Y-m-d');

            // -------------------------------------------------------------------------
            // STEP 1: Extract date from filename
            // -------------------------------------------------------------------------
            $extractedDate = null;
            $remainder = $nameNoExt;

            $datePatterns = [
                '/^(\d{4})[-\/\.](\d{2})[-\/\.](\d{2})(.*)$/',  // YYYY-MM-DD / YYYY/MM/DD / YYYY.MM.DD
                '/^(\d{4})_(\d{2})_(\d{2})_?(.*)$/',             // YYYY_MM_DD
                '/^(\d{4})(\d{2})(\d{2})[-_]?(.*)$/',            // YYYYMMDD (compact)
                '/^(\d{2})[-\/\.](\d{2})[-\/\.](\d{4})(.*)$/',   // DD-MM-YYYY
                '/^(\d{2})[-_](\d{2})[-_](\d{4})(.*)$/',         // MM-DD-YYYY
            ];

            foreach($datePatterns as $index => $pattern) {
                if(!preg_match($pattern, $nameNoExt, $m)) continue;

                [$y, $mo, $d] = match($index) {
                    3 => [$m[3], $m[2], $m[1]], // DD-MM-YYYY
                    4 => [$m[3], $m[1], $m[2]], // MM-DD-YYYY
                    default => [$m[1], $m[2], $m[3]],
                };

                $candidate = "{$y}-{$mo}-{$d}";
                $dateObj = DateTime::createFromFormat('Y-m-d', $candidate);

                if(!$dateObj || $dateObj->format('Y-m-d') !== $candidate) continue;
                if((int)$y < 2000 || $dateObj > new DateTime('today')) continue;

                $extractedDate = $candidate;
                $remainder = trim($m[4], '-_. ');
                break;
            }

            // No date found → use today, full name becomes the remainder
            if($extractedDate === null) {
                $extractedDate = $today;
                $remainder = $nameNoExt;
            }

            // -------------------------------------------------------------------------
            // STEP 2: Normalize remainder separators
            // -------------------------------------------------------------------------
            $remainder = preg_replace('/[-.\s]+/', '_', $remainder);
            $remainder = trim($remainder, '_');

            if($remainder === '') return null;

            // -------------------------------------------------------------------------
            // STEP 3: Detect optional version + name
            //
            // Structure after date: [Version_]Name
            // Version = purely numeric segment, either at BEGINNING or END of remainder.
            // Result is always: DATE_Version_Name (version moves to front).
            // -------------------------------------------------------------------------
            $segments = explode('_', $remainder);

            $version = null;
            $nameParts = $segments;

            // Version at the BEGINNING (e.g. "2_AddColumn")
            if(count($segments) > 1 && preg_match('/^\d+$/', $segments[0])) {
                $version = (int)$segments[0];
                $nameParts = array_slice($segments, 1);
            }
            // Version at the END (e.g. "AddColumn_2") → move it to front
            elseif(count($segments) > 1 && preg_match('/^\d+$/', end($segments))) {
                $version = (int)end($segments);
                $nameParts = array_slice($segments, 0, -1);
            }

            // -------------------------------------------------------------------------
            // STEP 4: Build and sanitize the name
            // -------------------------------------------------------------------------
            $name = implode('_', array_filter($nameParts, fn($p) => $p !== ''));

            if(empty($name)) return null;

            // Sanitize: keep only word characters
            if(!preg_match('/^\w+$/', $name)) {
                $name = preg_replace('/[^\w]/', '_', $name);
                $name = preg_replace('/_+/', '_', $name);
                $name = trim($name, '_');

                if(empty($name)) return null;
            }

            // -------------------------------------------------------------------------
            // STEP 5: Assemble final filename  →  DATE[_Version]_Name.ext
            // -------------------------------------------------------------------------
            $newName = $version !== null
                ? "{$extractedDate}_{$version}_{$name}"
                : "{$extractedDate}_{$name}";

            $newBasename = $extension !== '' ? "{$newName}.{$extension}" : $newName;

            // Already valid → nothing to do
            if($newBasename === $basename) return null;

            return $newBasename;
        }

    }