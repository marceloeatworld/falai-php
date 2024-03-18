<?php

namespace MarceloEatWorld\Falai;

use Saloon\Http\Connector;

class Resource
{
    public function __construct(protected Connector $connector)
    {
        //
    }
}