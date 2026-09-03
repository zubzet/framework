<?php

    namespace ZubZet\Framework\Translation;

    use ZubZet\Framework\Registry\Registry;
    use ZubZet\Framework\Support\StaticCache;
    use Symfony\Component\Translation\Translator;
    use Symfony\Component\Translation\Loader\IniFileLoader;
    use Symfony\Component\Translation\Loader\CsvFileLoader;
    use Symfony\Component\Translation\Loader\PhpFileLoader;
    use Symfony\Component\Translation\Loader\JsonFileLoader;

    class Translation {

        private const LOADERS = [
            "json" => JsonFileLoader::class,
            "php" => PhpFileLoader::class,
            "csv" => CsvFileLoader::class,
            "ini" => IniFileLoader::class,
        ];

        public static function translate(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string {
            $translator = StaticCache::getOrNull("translation", "translator");

            if(is_null($translator)) {
                $translator = new Translator(self::locale());
                $translator->setFallbackLocales(self::fallbackLocales());

                foreach(self::LOADERS as $format => $loader) {
                    $translator->addLoader($format, new $loader());
                }

                foreach(self::catalogues() as $catalogue) {
                    $translator->addResource(
                        $catalogue["format"],
                        $catalogue["path"],
                        $catalogue["locale"],
                        $catalogue["domain"],
                    );
                }

                StaticCache::set("translation", "translator", $translator);
            }

            return $translator->trans($id, $parameters, $domain, $locale);
        }

        // Every catalogue file, in the order the translator loads them.
        public static function catalogues(): array {
            $cached = StaticCache::getOrNull("translation", "catalogues");
            if(!is_null($cached)) return $cached;

            $catalogues = [];

            // Reversed: the message added last wins, so the application beats the framework.
            foreach(array_reverse(Registry::files("translations")) as $path) {

                // A catalogue is named {domain}.{locale}.{format}.
                $segments = explode(".", basename($path));
                if(count($segments) < 3) continue;

                $format = array_pop($segments);
                $locale = array_pop($segments);

                $catalogues[] = [
                    "format" => $format,
                    "path" => $path,
                    "locale" => $locale,
                    "domain" => implode(".", $segments),
                ];
            }

            return StaticCache::set("translation", "catalogues", $catalogues);
        }

        public static function locale(): string {
            $userBcp47 = user()?->fields["locale_bcp_47"] ?? null;
            if(!is_null($userBcp47)) return $userBcp47;



            return self::negotiatedLocale()
                ?? self::fallbackLocales()[0];
        }

        public static function fallbackLocales(): array {
            $fallbacks = config("fallback_locales", default: "en");

            return array_map("trim", explode(",", $fallbacks));
        }

        // The best Accept-Language entry that a catalogue actually covers.
        private static function negotiatedLocale(): ?string {

            // A locale with several domains brings its catalogue along more than once,
            // and the lookup below is capped at a hundred entries.
            $available = array_values(array_unique(array_column(self::catalogues(), "locale")));

            foreach(self::acceptedLocales() as $requested) {

                // RFC 4647 lookup truncates the request from the right until it reaches a
                // catalogue, so "de-CH-1901" prefers "de_CH" over "de" on its own. It sees
                // past the spelling difference between the header and the Symfony
                // catalogue, ignores case along the way, and answers in the catalogue's own
                // spelling. A miss is an empty string, and a tag too long for intl is null;
                // either way the next entry deserves its turn.
                $match = \Locale::lookup($available, $requested);
                if(!is_null($match) && "" !== $match) return $match;
            }

            return null;
        }

        private static function acceptedLocales(): array {
            $header = request()->acceptLanguage();
            if(is_null($header)) return [];

            $accepted = [];
            foreach(explode(",", $header) as $entry) {
                [$locale, $quality] = array_pad(explode(";q=", trim($entry), 2), 2, "1");

                // "*" accepts anything, which says nothing about which catalogue to pick.
                if("" === $locale || "*" === $locale) continue;

                $accepted[] = ["locale" => $locale, "quality" => (float) $quality];
            }

            // usort is stable as of PHP 8.0, so equal qualities keep the order the client sent.
            usort($accepted, fn($a, $b) => $b["quality"] <=> $a["quality"]);

            return array_column($accepted, "locale");
        }

    }

?>
