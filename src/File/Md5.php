<?php

/**
 * @see       https://github.com/laminas/laminas-validator for the canonical source repository
 * @copyright https://github.com/laminas/laminas-validator/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-validator/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Validator\File;

use Laminas\Validator\Exception;

/**
 * Validator for the md5 hash of given files
 */
class Md5 extends Hash
{
    /**
     * @const string Error constants
     */
    const DOES_NOT_MATCH = 'fileMd5DoesNotMatch';
    const NOT_DETECTED   = 'fileMd5NotDetected';
    const NOT_FOUND      = 'fileMd5NotFound';

    /**
     * @var array Error message templates
     */
    protected $messageTemplates = array(
        self::DOES_NOT_MATCH => "File does not match the given md5 hashes",
        self::NOT_DETECTED   => "A md5 hash could not be evaluated for the given file",
        self::NOT_FOUND      => "File is not readable or does not exist",
    );

    /**
     * Options for this validator
     *
     * @var string
     */
    protected $options = array(
        'algorithm' => 'md5',
        'hash'      => null,
    );

    /**
     * Returns all set md5 hashes
     *
     * @return array
     */
    public function getMd5()
    {
        return $this->getHash();
    }

    /**
     * Sets the md5 hash for one or multiple files
     *
     * @param  string|array $options
     * @return Hash Provides a fluent interface
     */
    public function setMd5($options)
    {
        $this->setHash($options);
        return $this;
    }

    /**
     * Adds the md5 hash for one or multiple files
     *
     * @param  string|array $options
     * @return Hash Provides a fluent interface
     */
    public function addMd5($options)
    {
        $this->addHash($options);
        return $this;
    }

    /**
     * Returns true if and only if the given file confirms the set hash
     *
     * @param  string|array $value Filename to check for hash
     * @return bool
     */
    public function isValid($value)
    {
        if (is_array($value)) {
            if (!isset($value['tmp_name']) || !isset($value['name'])) {
                throw new Exception\InvalidArgumentException(
                    'Value array must be in $_FILES format'
                );
            }
            $file     = $value['tmp_name'];
            $filename = $value['name'];
        } else {
            $file     = $value;
            $filename = basename($file);
        }
        $this->setValue($filename);

        // Is file readable ?
        if (false === stream_resolve_include_path($file)) {
            $this->error(self::NOT_FOUND);
            return false;
        }

        $hashes = array_unique(array_keys($this->getHash()));
        $filehash = hash_file('md5', $file);
        if ($filehash === false) {
            $this->error(self::NOT_DETECTED);
            return false;
        }

        foreach ($hashes as $hash) {
            if ($filehash === $hash) {
                return true;
            }
        }

        $this->error(self::DOES_NOT_MATCH);
        return false;
    }
}
