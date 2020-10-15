<?php
namespace TypeRocket\Models;

/**
 * Class is only a stub for the container
 *
 * Container loads App\User
 */
interface AuthUser
{
    public const ALIAS = 'user';

    /**
     * @return bool
     */
    public function isCurrent();

    /**
     * @param $capability
     *
     * @return bool
     */
    public function isCapable($capability);

    /**
     * @return int
     */
    public function getID();
}