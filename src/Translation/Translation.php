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

        // symfony supported translation loaders
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

        // The preferred locale from the user in bcp47 format
        public static function locale(): string {
            // User locale
            $userBcp47 = user()?->fields["locale_bcp_47"] ?? null;
            if(!is_null($userBcp47)) return $userBcp47;

            // Available locales from the loaded catalogues
            $available = array_values(array_unique(array_column(self::catalogues(), "locale")));

            // The best Accept-Language entry that a catalogue actually covers.
            foreach(request()->acceptLanguage() as $requested) {
                // Match the requested locale against the available locales, using the best-fit algorithm.
                $match = \Locale::lookup($available, $requested);
                if(!empty($match)) return $match;
            }

            // Otherwise return the first fallback locale
            return self::fallbackLocales()[0];
        }

        public static function fallbackLocales(): array {
            $fallbacks = config("fallback_locales", default: "en");
            return array_map("trim", explode(",", $fallbacks));
        }
    }

?>
