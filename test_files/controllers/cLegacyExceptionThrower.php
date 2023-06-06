<?php

class cLegacyExceptionThrower extends OObject
{
    public function __construct()
    {
        $this->permissions = [
            'object' => 'any',
            'throw' => 'any',
            'nestToThrow' => 'any',
        ];
    }

    public function throw()
    {
        throw new Exception();
    }

    public function nestToThrow()
    {
        $this->route('LegacyExceptionThrower/throw');
    }
}
