<?php
namespace TypeRocket\Auth;

use TypeRocket\Models\AuthUser;

abstract class Policy
{
    protected $user;

    public function __construct(AuthUser $user)
    {
        $this->user = $user;
    }

}