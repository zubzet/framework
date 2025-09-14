<?php

    namespace ZubZet\Tooling\Modifiers;

    use Symfony\Component\Console\Input\InputInterface;
    use ZubZet\Tooling\Upgrade;
    use ZubZet\Tooling\Version\BaseVersion;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Tooling\Exceptions\AbortRequiringUserAction;
    use Symfony\Component\Console\Helper\QuestionHelper;
    use Symfony\Component\Console\Question\ConfirmationQuestion;

    class BaseModifier {
        protected BaseVersion $version;
        protected Upgrade $upgrade;

        protected InputInterface $in;
        protected OutputInterface $out;

        protected string $stepName;

        public function __construct(BaseVersion $version, string $stepName) {
            $this->version = $version;
            $this->upgrade = $version->upgrade;

            $this->in = $this->upgrade->input;
            $this->out = $this->upgrade->output;

            $this->stepName = $version->semanticVersion."-$stepName";
            $this->out->writeln("==> Running '{$this->stepName}' ...");

            $this->configure();
        }

        protected function configure() {}

        public function confirmAutomatedChange(): bool{
            if($this->upgrade->dry) return false;
            if($this->shouldSkipStep()) return false;

            $helper = new QuestionHelper();
            $question = new ConfirmationQuestion('Do you want to apply the automated change now? [Y/N]: ', false);

            $this->out->writeln("");
            return $helper->ask($this->in, $this->out, $question);
        }

        public function shouldSkipStep() {
            return in_array($this->stepName, $this->upgrade->skipSteps);
        }

        // Do not perform any more actions when an upgrade is aborted
        private bool $abortedRequiringUserAction = false;
        public function abortRequiringUserAction() {
            if($this->shouldSkipStep()) {
                $this->out->writeln("<comment>Step '{$this->stepName}' is skipped, continuing...</comment>");
                return;
            }

            if($this->upgrade->dry) return;

            $this->abortedRequiringUserAction = true;
            $this->upgrade->dry = true;
            throw new AbortRequiringUserAction($this->stepName);
        }
    }

?>