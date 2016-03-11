<?php

namespace Moccalotto\Reporter;

/**
 * Signs a report so the recipient can validate the sender.
 */
class Signer
{
    /**
     * HMAC key.
     *
     * @var string
     */
    protected $key;

    /**
     * HMAC algorith.
     *
     * @var string
     */
    protected $algorithm;

    /**
     * Constructor.
     *
     * @param string $algorithm
     * @param string $key
     */
    public function __construct($algorithm, $key)
    {
        $this->algorithm = $algorithm;
        $this->key = $key;
    }

    /**
     * Create a signature for a given string.
     *
     * @param string $data
     */
    public function signature($data)
    {
        return hash_hmac($this->algorithm, $data, $this->key);
    }
}
