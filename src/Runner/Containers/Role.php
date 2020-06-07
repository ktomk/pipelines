<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use UnexpectedValueException;

/**
 * Class Role
 *
 * @package Ktomk\Pipelines\Runner\Containers
 */
abstract class Role
{
    public static $roles = array('pipe', 'service', 'step');

    /**
     * @param string $role
     *
     * @throws UnexpectedValueException
     *
     * @return string representing a container role
     */
    public static function verify($role)
    {
        $roles = self::$roles;
        if (in_array($role, $roles, true)) {
            return $role;
        }

        throw new UnexpectedValueException(
            sprintf('Not a role: "%s"; Roles are: "%s"', $role, implode('", "', $roles))
        );
    }
}
