<?php

    namespace ZubZet\Framework\Rendering\Katana;

    use Blade\Blade;
    use Blade\ComponentRenderer as KatanaComponentRenderer;
    use Blade\Exceptions\BladeException;

    /**
     * Gives every component the framework's standard $opt (root, host, etc.), so it never has to be
     * passed manually. What the caller passed to render() is not part of it.
     *
     * In return the attribute name is reserved: <x-card :opt="$opt"/> is refused, so the
     * framework can change what $opt holds in a component without breaking one that
     * receives something else under that name. Only the framework's own zubzet::
     * components may still take the full $opt of the view.
     */
    class ComponentRenderer extends KatanaComponentRenderer {

        public function __construct(Blade $blade, private array $standardOpt) {
            parent::__construct($blade);
        }

        public function prepare(string $namespace, string $name, $data = [], bool $componentDirectiveCompatibility = false) {
            $isReserved = "zubzet" !== $namespace && array_key_exists("opt", (array) $data);
            if($isReserved) {
                throw new BladeException("The component '$name' is handed 'opt', which is reserved: every component has \$opt with the framework values (root, user, ...) already, pass anything else as its own attribute, e.g. :title=\"\$title\".");
            }

            parent::prepare($namespace, $name, $data, $componentDirectiveCompatibility);
        }

        public function getViewData(): array {
            return array_merge(
                ["opt" => $this->standardOpt],
                parent::getViewData(),
            );
        }
    }

?>
