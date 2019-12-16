<?php

/* this file is part of pipelines */

if (!class_exists('PHPUnit\Framework\Exception')) {
    class_alias('PHPUnit_Framework_Exception', 'PHPUnit\Framework\Exception');
}

if (!interface_exists('PHPUnit\Framework\MockObject\MockObject')) {
    class_alias('PHPUnit_Framework_MockObject_MockObject', 'PHPUnit\Framework\MockObject\MockObject');
}

if (!class_exists('PHPUnit\Runner\BaseTestRunner')) {
    class_alias('PHPUnit_Runner_BaseTestRunner', 'PHPUnit\Runner\BaseTestRunner');
}
