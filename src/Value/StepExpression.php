<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value;

use Ktomk\Pipelines\File\Pipeline\Steps;
use Ktomk\Pipelines\Lib;

/**
 * Class StepExpression
 *
 * @package Ktomk\Pipelines\Value
 */
class StepExpression
{
    /**
     * @var array 1-3, -5, 1-
     */
    private $segments;

    /**
     * @param string $expression
     *
     * @return StepExpression
     */
    public static function createFromString($expression)
    {
        return new self(self::parseSegments($expression));
    }

    /**
     * Is a step expression syntactically valid?
     *
     * @param string $expression
     *
     * @return bool
     */
    public static function validate($expression)
    {
        try {
            self::parseSegments($expression);
        } catch (\InvalidArgumentException $ex) {
            return false;
        }

        return true;
    }

    /**
     * NOTE: this static function needs to be public for PHP backwards compat
     *
     * @param string $segment
     * @param int $count
     *
     * @return array|int[]
     *
     * @see resolveSegmentFunctor
     */
    public static function resolveSegmentCount($segment, $count)
    {
        $pos = strpos($segment, '-');
        list($from, $to) = explode('-', $segment, 2) + array('', '');

        if (false === $pos) {
            return array(self::verifyValueCount((int)Lib::emptyCoalesce($from, $to), $count));
        }

        if (empty($to)) {
            return range(self::verifyValueCount((int)$from, $count), $count);
        }

        if (empty($from)) {
            return range(self::verifyValueCount(1, $count), self::verifyValueCount($to, $count));
        }

        return range(self::verifyValueCount((int)$from, $count), self::verifyValueCount($to, $count));
    }

    /**
     * StepExpression constructor.
     *
     * @param array $segments
     */
    public function __construct(array $segments)
    {
        $this->segments = $segments;
    }

    /**
     * @return array
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @param int $count
     *
     * @return array|int[]
     */
    public function resolveCount($count)
    {
        if (0 === $count || !is_int($count)) {
            throw new \InvalidArgumentException('Count is zero');
        }

        $range = Lib::merge(array_map(self::resolveSegmentFunctor($count), $this->segments));

        return call_user_func_array('array_merge', $range);
    }

    /**
     * @param mixed $countable
     *
     * @return array|int[]
     */
    public function resolveCountable($countable)
    {
        return $this->resolveCount(count($countable));
    }

    /**
     * @param Steps $steps
     *
     * @return array|Steps[]
     */
    public function resolveSteps(Steps $steps)
    {
        $stepNumbers = $this->resolveCountable($steps);
        $array = array();
        foreach ($stepNumbers as $number) {
            $array[] = $steps[$number - 1];
        }

        return $array;
    }

    /**
     * @param string $buffer
     *
     * @return array|string[]
     */
    private static function parseSegments($buffer)
    {
        $normalized = preg_replace(
            array('~\s+~', '~(\d+)\s+(-)~', '~(-)\s+(\d+)+~'),
            array(' ', '\\1\\2', '\\1\\2'),
            $buffer
        );

        if (empty($normalized)) {
            throw new \InvalidArgumentException(sprintf('No steps: "%s"', $buffer));
        }

        $result = preg_split('~\s*(?:,|\s)\s*~', $normalized, 64, PREG_SPLIT_NO_EMPTY);
        if (empty($result)) {
            throw new \InvalidArgumentException(sprintf('No steps: "%s"', $buffer));
        }

        $array = array();
        foreach ($result as $segmentBuffer) {
            $array[] = self::parseSegment($segmentBuffer);
        }

        return $array;
    }

    /**
     * Parse (better: normalize) segment
     *
     * @param string $buffer
     *
     * @return string
     */
    private static function parseSegment($buffer)
    {
        $segment = preg_replace('~\s+~', '', $buffer);
        if (null === $segment) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(sprintf('Can not parse step segment for "%s"', $buffer));
            // @codeCoverageIgnoreEnd
        }

        $result = preg_match('~^(?:(?:[1-9]\d*)?-(?:[1-9]\d*)?|(?:[1-9]\d*))$~', $segment);
        if (false === $result || 0 === $result || '-' === $segment) {
            if ($buffer !== $segment) {
                // @codeCoverageIgnoreStart
                throw new \InvalidArgumentException(
                    sprintf('Can not parse step segment for "%s" (from "%s")', $segment, $buffer)
                );
                // @codeCoverageIgnoreEnd
            }

            throw new \InvalidArgumentException(sprintf('Can not parse step segment for "%s"', $segment));
        }

        return $segment;
    }

    /**
     * @param int $count
     *
     * @return \Closure
     */
    private static function resolveSegmentFunctor($count)
    {
        return function ($segment) use ($count) {
            return StepExpression::resolveSegmentCount($segment, $count);
        };
    }

    /**
     * @param int $value
     * @param int $count
     *
     * @return int
     */
    private static function verifyValueCount($value, $count)
    {
        if ($value > $count) {
            throw new \InvalidArgumentException(sprintf('step %d is out of range, highest is %d', $value, $count));
        }

        return $value;
    }
}
