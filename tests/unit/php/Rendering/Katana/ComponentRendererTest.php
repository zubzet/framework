<?php

    namespace Tests\Rendering\Katana;

    use Blade\Blade;
    use Blade\Config;
    use Blade\Exceptions\BladeException;
    use Blade\FileSystemViewFinder;
    use Tests\TestCase;
    use ZubZet\Framework\Rendering\Katana\ComponentRenderer;

    class ComponentRendererTest extends TestCase {

        private const STANDARD_OPT = ["root" => "/standard/", "title" => "Standard"];

        private const RESERVED = "The component 'card' is handed 'opt', which is reserved: every component has \$opt with the framework values (root, user, ...) already, pass anything else as its own attribute, e.g. :title=\"\$title\".";

        private string $views;
        private Blade $blade;

        protected function setUp(): void {
            parent::setUp();

            $this->views = $this->temporaryDirectory();
            mkdir($this->views . "components");
            mkdir($this->views . "framework");

            $config = new Config($this->temporaryDirectory());
            $config->addViewFinder(new FileSystemViewFinder($this->views));

            $this->blade = new Blade(config: $config);
            $this->blade->addAnonymousComponentPath($this->views . "framework", "zubzet");
            $this->blade->componentRenderer = new ComponentRenderer($this->blade, self::STANDARD_OPT);
        }

        // Standard $opt

        public function testAComponentHasTheStandardOptWithoutAnAttribute(): void {
            $this->component("card", '[{{ $opt["root"] }}|{{ $opt["title"] }}]');
            $this->view("page", "<x-card/>");

            $this->assertSame("[/standard/|Standard]", $this->render("page"));
        }

        public function testANestedComponentHasTheStandardOptToo(): void {
            $this->component("outer", "<x-inner/>");
            $this->component("inner", '[{{ $opt["root"] }}]');
            $this->view("page", "<x-outer/>");

            $this->assertSame("[/standard/]", $this->render("page"));
        }

        public function testAComponentWithPropsKeepsTheStandardOpt(): void {
            $this->component("card", '@props(["label" => "default"])[{{ $label }}|{{ $opt["root"] }}]');
            $this->view("page", '<x-card label="given"/>');

            $this->assertSame("[given|/standard/]", $this->render("page"));
        }

        public function testTheRenderDataOfTheViewIsNotPartOfIt(): void {
            $this->component("card", '[{{ $opt["caller"] ?? "none" }}|{{ $caller ?? "none" }}]');
            $this->view("page", '{{ $caller }}<x-card/>');

            $data = ["caller" => "CALLER", "opt" => ["caller" => "CALLER"]];

            $this->assertSame("CALLER[none|none]", $this->render("page", $data));
        }

        public function testTheComponentKeepsItsOwnAttributesAndSlot(): void {
            $this->component("card", '[{{ $label }}|{{ $slot }}|{{ $opt["root"] }}]');
            $this->view("page", '<x-card label="given">content</x-card>');

            $this->assertSame("[given|content|/standard/]", $this->render("page"));
        }

        // Reserved attribute

        /** @dataProvider reservedOptValues */
        public function testRefusesAnAppComponentThatIsHandedOpt(mixed $value): void {
            $this->assertThrows(BladeException::class, self::RESERVED, function() use ($value) {
                $this->blade->componentRenderer->prepare("", "card", ["opt" => $value]);
            });
        }

        public static function reservedOptValues(): array {
            return [
                "the opt of the view" => [["root" => "/"]],
                "a plain string" => ["text"],
                "null" => [null],
            ];
        }

        public function testRefusesItInComponentDirectiveModeToo(): void {
            $this->assertThrows(BladeException::class, self::RESERVED, function() {
                $this->blade->componentRenderer->prepare("", "card", ["opt" => []], true);
            });
        }

        public function testRefusesItForAnotherNamespaceThanTheFrameworkOne(): void {
            $this->assertThrows(BladeException::class, self::RESERVED, function() {
                $this->blade->componentRenderer->prepare("shop", "card", ["opt" => []]);
            });
        }

        public function testAFrameworkComponentTakesTheOptItIsHanded(): void {
            file_put_contents($this->views . "framework/head.blade.php", '[{{ $opt["root"] }}]');
            $this->view("page", '<x-zubzet::head :opt="$opt"/>');

            $this->assertSame("[/view/]", $this->render("page", ["opt" => ["root" => "/view/"]]));
        }

        public function testAFrameworkComponentWithoutTheAttributeHasTheStandardOpt(): void {
            file_put_contents($this->views . "framework/head.blade.php", '[{{ $opt["root"] }}]');
            $this->view("page", "<x-zubzet::head/>");

            $this->assertSame("[/standard/]", $this->render("page"));
        }

        private function view(string $name, string $contents): void {
            file_put_contents($this->views . "$name.blade.php", $contents);
        }

        private function component(string $name, string $contents): void {
            $this->view("components/$name", $contents);
        }

        private function render(string $view, array $data = []): string {
            return trim((string) $this->blade->render($view, $data));
        }
    }

?>
