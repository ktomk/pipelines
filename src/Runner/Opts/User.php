<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Opts;

use Ktomk\Pipelines\Preg;

/**
 * <name|uid>[:<group|gid>]
 *
 * from --user[=<name|uid>[:<group|gid>]]
 */
final class User
{
    /**
     * @var string
     */
    private $user;

    /**
     * @param non-empty-string $user
     *
     * @return self
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * @return array{0: int, 1: int|null}
     */
    public function toUidGidArray()
    {
        list($user, $group) = explode(':', $this->user, 2) + array(null, null);

        /* --user=0:0 */
        if ($this->isId($user) && $this->isId($group)) {
            return array((int)$user, (int)$group);
        }

        /* --user=0 */
        if ($this->isId($user) && null === $group) {
            return array((int)$user, $group);
        }

        /* --user=name[:group] */
        $match = Preg::match('/^[a-zA-Z0-9_.][a-zA-Z0-9_.-]{0,30}[a-zA-Z0-9_.$-]?$/', $user);
        if (0 === $match) {
            throw new \RuntimeException(sprintf('illegal username: %s', addcslashes($user, "\0..\40'\177..\377")));
        }

        throw new \RuntimeException(sprintf('unable to find user %s: there is no owner and group map. use numeric user/group ids instead.', $user));
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->user;
    }

    /**
     * @param string $subject
     *
     * @return bool
     */
    private function isId($subject)
    {
        return (
            $subject === (string)(int)$subject
            && '-' !== $subject[0]
        );
    }
}
