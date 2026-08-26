<?php

    namespace ZubZet\Framework\Translation;

use Symfony\Component\Translation\Loader\CsvFileLoader;
use Symfony\Component\Translation\Loader\IniFileLoader;
use ZubZet\Framework\Registry\Registry;
    use ZubZet\Framework\Support\StaticCache;
    use Symfony\Component\Translation\Translator;
    use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;

    class Translation {

        public Translator $translator;

        private array $fileLoader = [
            "json" => JsonFileLoader::class,
            "yaml" => YamlFileLoader::class,
            "xlf" => XliffFileLoader::class,
            "php" => PhpFileLoader::class,
            "csv" => CsvFileLoader::class,
            "ini" => IniFileLoader::class,
        ];

        private function __construct() {
            $this->translator = new Translator(self::locale());

            // Fallback locales are read in order for messages the active locale does not define.
            $this->translator->setFallbackLocales(self::fallbackLocales());

            // Load supported file formats for translation catalogues.
            foreach($this->fileLoader as $format => $loader) {
                $this->translator->addLoader($format, new $loader());
            }

            // Load every translation-file
            foreach(self::catalogues() as $catalogue) {
                $this->translator->addResource(
                    $catalogue["format"],
                    $catalogue["path"],
                    $catalogue["locale"],
                    $catalogue["domain"],
                );
            }
        }

        public static function translate(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string {
            return self::translator()->trans($id, $parameters, $domain, $locale);
        }

        // Symfony translator, built once per request.
        public static function translator(): Translator {
            $translator = StaticCache::getOrNull("translation", "translator");
            if(is_null($translator)) {
                $translator = StaticCache::set("translation", "translator", (new self())->translator);
            }

            return $translator;
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
                $domain = implode(".", $segments);

                $catalogues[] = [
                    "format" => $format,
                    "path" => $path,
                    "locale" => $locale,
                    "domain" => $domain,
                ];
            }

            return StaticCache::set("translation", "catalogues", $catalogues);
        }

        // Read in order for messages the active locale does not define.
        public static function fallbackLocales(): array {
            $fallbacks = config("fallback_locales", default: "en");

            return array_map("trim", explode(",", $fallbacks));
        }


        // TODO: Replace with user-identified locale, or browser-identified locale.
        public static function locale(): string {
            return "en";
        }

    }

?>
