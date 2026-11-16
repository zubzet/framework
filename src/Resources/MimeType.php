<?php

    namespace ZubZet\Framework\Resources;

    /**
     * The content type of an asset, read off its file extension.
     * The content of the file is never inspected.
     */
    final class MimeType {

        public const FALLBACK = "application/octet-stream";

        /** What a web application ships as an asset. */
        private const BY_EXTENSION = [
            // Code and text
            "css" => "text/css",
            "js" => "application/javascript",
            "mjs" => "text/javascript",
            "json" => "application/json",
            "map" => "application/json",
            "webmanifest" => "application/manifest+json",
            "wasm" => "application/wasm",
            "html" => "text/html",
            "htm" => "text/html",
            "txt" => "text/plain",
            "md" => "text/markdown",
            "csv" => "text/csv",
            "xml" => "application/xml",
            "yml" => "text/yaml",
            "yaml" => "text/yaml",
            "scss" => "text/x-scss",
            "less" => "text/less",
            "sql" => "application/x-sql",

            // Images
            "svg" => "image/svg+xml",
            "png" => "image/png",
            "jpg" => "image/jpeg",
            "jpeg" => "image/jpeg",
            "gif" => "image/gif",
            "webp" => "image/webp",
            "avif" => "image/avif",
            "ico" => "image/x-icon",
            "bmp" => "image/bmp",

            // Fonts
            "woff" => "font/woff",
            "woff2" => "font/woff2",
            "ttf" => "font/ttf",
            "otf" => "font/otf",
            "eot" => "application/vnd.ms-fontobject",

            // Audio and video
            "mp3" => "audio/mpeg",
            "ogg" => "audio/ogg",
            "wav" => "audio/x-wav",
            "mp4" => "video/mp4",
            "webm" => "video/webm",

            // Documents and archives
            "pdf" => "application/pdf",
            "zip" => "application/zip",
            "gz" => "application/gzip",
        ];

        /** The content type for the extension of the path, or the fallback for an extension that is not known. */
        public static function fromPath(string $path): string {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            return self::BY_EXTENSION[$extension] ?? self::FALLBACK;
        }
    }

?>
