<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

/**
 * Class References
 *
 * Utility class for pipeline references (ids) definition and validation
 *
 * pipeline        : that what can be referenced (id): default, branches/**, custom/foo etc.
 * section         : branches, tags, bookmarks, pull-requests, custom
 * pattern section : a section that can match by pattern to some input (all sections but custom)
 * segments        : constants for reference segments, default for the default pipeline and custom
 *                   for custom pipelines. they come with no mapping to any vcs nomenclature.
 *
 * @package Ktomk\Pipelines\File
 */
class ReferenceTypes
{
    const REF_BRANCHES = 'branches';
    const REF_TAGS = 'tags';
    const REF_BOOKMARKS = 'bookmarks';
    const REF_PULL_REQUESTS = 'pull-requests';

    const SEG_DEFAULT = 'default';
    const SEG_CUSTOM = 'custom';

    /**
     * pipelines sections on first level that contain pipelines on second level
     *
     * @var array
     */
    private static $sections = array(
        self::REF_BRANCHES, self::REF_TAGS, self::REF_BOOKMARKS, self::REF_PULL_REQUESTS, self::SEG_CUSTOM,
    );

    /**
     * @param string $id
     *
     * @return bool
     */
    public static function isValidId($id)
    {
        return (bool)preg_match(
            '~^(' . self::SEG_DEFAULT . '|(' . implode('|', self::$sections) . ')/[^\x00-\x1F\x7F-\xFF]*)$~',
            $id
        );
    }

    /**
     * a section that contains pipelines that can match by pattern
     *
     * @param null|int|string $section
     *
     * @return bool
     *
     * @psalm-assert-if-true string $section
     */
    public static function isPatternSection($section)
    {
        return is_string($section) && \in_array($section, array_slice(self::$sections, 0, 4), true);
    }

    /**
     * @param null|int|string $section
     *
     * @return bool
     */
    public static function isSection($section)
    {
        return is_string($section) && in_array($section, self::$sections, true);
    }

    /**
     * get sections
     *
     * @return array|string[]
     */
    public static function getSections()
    {
        return self::$sections;
    }
}
