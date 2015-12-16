<?php

namespace Moccalotto\Reporter;

class Signer
{
    protected $key;
    protected $algorithm;

    public function __construct($algorithm, $key)
    {
        $this->algorithm = $algorithm;
        $this->key = $key;
    }

    public function signature($data)
    {
        return hash_hmac($this->algorithm, $data, $this->key);
    }
}
