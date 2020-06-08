<?php

namespace PolymerMallard\Console\Input;


class MockInput extends \Symfony\Component\Console\Input\ArrayInput
{

    public function getOption($key)
    {
        return $this->getParameterOption($key);
    }

}
