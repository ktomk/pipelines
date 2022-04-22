<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Show;

class FileTable
{
    /**
     * @var int
     */
    private $errors = 0;

    /**
     * @var array
     */
    private $table;

    public function __construct(array $firstRow)
    {
        $this->addRow($firstRow);
    }

    /**
     * @param array $row
     *
     * @return $this
     */
    public function addRow(array $row)
    {
        $this->table[] = $row;

        return $this;
    }

    /**
     * @param mixed $noError
     * @param array $row
     *
     * @return $this
     */
    public function addFlaggedRow($noError, array $row)
    {
        if (!$noError) {
            $this->errors++;
        }

        return $this->addRow($row);
    }

    /**
     * @param array $row
     *
     * @return $this
     */
    public function addErrorRow(array $row)
    {
        return $this->addFlaggedRow(false, $row);
    }

    /**
     * @return int
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->table;
    }
}
