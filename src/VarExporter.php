<?php

declare(strict_types=1);

namespace Brick\VarExporter;

class VarExporter
{
    /**
     * @param mixed $var A variable to export.
     *
     * @return string
     *
     * @throws ExportException
     */
    public function export($var) : string
    {
        return $this->doExport($var, 0);
    }

    /**
     * @param mixed $var
     * @param int   $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    private function doExport($var, int $nestingLevel) : string
    {
        switch ($type = gettype($var)) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
                return var_export($var, true);

            case 'NULL':
                // lowercase null
                return 'null';

            case 'array':
                return $this->exportArray($var, $nestingLevel);

            case 'object':
                return $this->exportObject($var, $nestingLevel);

            default:
                throw new ExportException(sprintf('Type "%s" is not supported.', $type));
        }
    }

    /**
     * @param array $array
     * @param int   $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    private function exportArray(array $array, int $nestingLevel) : string
    {
        if (! $array) {
            return '[]';
        }

        $result = '[' . PHP_EOL;

        $isNumeric = array_keys($array) === range(0, count($array) - 1);

        $count = count($array);
        $current = 0;

        foreach ($array as $key => $value) {
            $isLast = (++$current === $count);
            $result .= $this->indent($nestingLevel + 1);

            if (! $isNumeric) {
                $result .= var_export($key, true);
                $result .= ' => ';
            }

            $result .= $this->doExport($value, $nestingLevel + 1);

            if (! $isLast) {
                $result .= ',';
            }

            $result .= PHP_EOL;
        }

        $result .= $this->indent($nestingLevel);
        $result .= ']';

        return $result;
    }

    /**
     * @param object $object
     * @param int    $nestingLevel
     *
     * @return string
     *
     * @throws ExportException
     */
    private function exportObject($object, int $nestingLevel) : string
    {
        if ($object instanceof \stdClass) {
            return '(object) ' . $this->exportArray((array) $object, $nestingLevel);
        }

        $values = get_object_vars($object);

        if (! $values) {
            return 'new ' . '\\' . get_class($object);
        }

        $result = '(function() {' . PHP_EOL;
        $result .= $this->indent($nestingLevel + 1);
        $result .= '$object = new \\' . get_class($object) . ';' . PHP_EOL;

        foreach ($values as $key => $value) {
            $result .= $this->indent($nestingLevel + 1);
            $result .= '$object->' . $this->escapeObjectVar($key) . ' = ' . $this->doExport($value, $nestingLevel + 1) . ';' . PHP_EOL;
        }

        $result .= PHP_EOL;
        $result .= $this->indent($nestingLevel + 1);
        $result .= 'return $object;' . PHP_EOL;

        $result .= $this->indent($nestingLevel);
        $result .= ')()';

        return $result;
    }

    /**
     * @param string $var
     *
     * @return string
     */
    private function escapeObjectVar(string $var) : string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $var) === 1) {
            return $var;
        }

        return '{' . var_export($var, true) . '}';
    }

    /**
     * @param int $nestingLevel
     *
     * @return string
     */
    private function indent(int $nestingLevel) : string
    {
        return str_repeat(' ', 4 * $nestingLevel);
    }
}
