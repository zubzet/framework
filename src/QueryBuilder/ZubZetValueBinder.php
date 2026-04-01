<?php
    namespace ZubZet\Framework\QueryBuilder;

    /**
     * This class extends CakePHP`s ValueBinder to change the placeholder format to '?'
     * and to change the bind method to match that of mysqli.
     * This allows us to use CakePHP`s Query Builder while still executing queries as
     * prepared statements using mysqli, which is much faster than using PDO (which CakePHP uses by default).
     */

    class ZubZetValueBinder extends \Cake\Database\ValueBinder {

        public function placeholder(string $token): string {
            return '?';
        }

        public function bind($param, $value, $type = null): void {
            $this->_bindings[] = [
                'value' => $value,
                'type' => $type,
                'placeholder' => '?',
            ];
        }

        public function generateManyNamed(iterable $values, $type = null): array {
            $placeholders = [];

            foreach($values as $k => $value) {
                $this->_bindings[] = [
                    'value' => $value,
                    'type' => $type,
                    'placeholder' => '?',
                ];
                $placeholders[$k] = '?';
            }

            return $placeholders;
        }
    }