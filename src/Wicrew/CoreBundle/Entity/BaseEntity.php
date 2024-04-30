<?php

namespace App\Wicrew\CoreBundle\Entity;

/**
 * BaseEntity
 */
abstract class BaseEntity {

    /**
     * Encrypt types
     */
    const ENCRYPT_TYPE_JSON = 1;
    const ENCRYPT_TYPE_SERIALIZE = 2;

    /**
     * Encrypt
     *
     * @param mixed $value
     * @param int $type
     *
     * @return mixed
     */
    protected function encrypt($value, $type = self::ENCRYPT_TYPE_JSON) {
        $result = false;

        if ($type == self::ENCRYPT_TYPE_JSON) {
            $result = json_encode($value);
        } else if ($type == self::ENCRYPT_TYPE_SERIALIZE) {
            $result = serialize($value);
        }

        return $result;
    }

    /**
     * Decrypt
     *
     * @param mixed $value
     * @param int $type
     * @param mixed $dafaultValue
     *
     * @return mixed
     */
    protected function decrypt($value, $type = self::ENCRYPT_TYPE_JSON, $dafaultValue = null) {
        $result = false;

        if ($type == self::ENCRYPT_TYPE_JSON) {
            $result = json_decode($value, true);
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    break;
                case JSON_ERROR_DEPTH:
                    //                    return 'Maximum stack depth exceeded';
                case JSON_ERROR_STATE_MISMATCH:
                    //                    return 'Underflow or the modes mismatch';
                case JSON_ERROR_CTRL_CHAR:
                    //                    return 'Unexpected control character found';
                case JSON_ERROR_SYNTAX:
                    //                    return 'Syntax error, malformed JSON';
                case JSON_ERROR_UTF8:
                    //                    return 'Malformed UTF-8 characters, possibly incorrectly encoded';
                default:
                    //                    return 'Unknown error';
                    $result = false;
                    break;
            }
        } else if ($type == self::ENCRYPT_TYPE_SERIALIZE) {
            $result = unserialize($value);
        }

        return !$result && !is_null($dafaultValue) ? $dafaultValue : $result;
    }

    public function __clone() {
        if (method_exists($this, 'setId')) {
            $this->setId(null);
        }
    }

    public function equalsID(BaseEntity $other): bool {
        if (method_exists($this, 'getId') && method_exists($other, 'getId')) {
            return $this->getId() === $other->getId();
        }

        return false;
    }
}
