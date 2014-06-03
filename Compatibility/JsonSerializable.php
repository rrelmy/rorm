<?php
/**
 * Only load this file in PHP < 5.4
 *
 * @author Rémy M. Böhler <code@rrelmy.ch>
 * @link http://www.php.net/manual/en/class.jsonserializable.php
 */

if (!interface_exists('JsonSerializable', false)) {
    /**
     * Objects implementing JsonSerializable can customize their JSON representation when encoded with json_encode().
     */
    interface JsonSerializable
    {
        /**
         * Specify data which should be serialized to JSON
         *
         * @return mixed
         */
        public function jsonSerialize();
    }
}
