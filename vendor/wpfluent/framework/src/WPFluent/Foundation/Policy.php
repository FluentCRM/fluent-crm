<?php

namespace FluentCrm\Framework\Foundation;

abstract class Policy
{
    /**
     * Fallback method even if verifyRequest is not implemented.
     * @return bool true
     */
    public function __returnTrue()
    {
        return true;
    }
}
