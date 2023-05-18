<?php

class cLegacyExceptionThrower extends OObject
{
    public function __construct()
    {
        $this->permissions = [
            'object' => 'any',
            'throw' => 'any',
        ];
    }

    public function throw()
    {
        throw new Exception();
    }
}
