<?php
namespace TypeRocket\Utility;

class Arr
{
    /**
     * Dots Meld
     *
     * Flatten array dimensions to one level and meld keys into dot
     * notation. liken meld to ['key.child' => 'value'].
     *
     * @param array $array the values to meld
     *
     * @return array
     */
    public static function meld(array $array) : array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
        $result = [];
        foreach ($iterator as $value) {
            $keys = [];
            $depth = range(0, $iterator->getDepth());
            foreach ($depth as $step) {
                $keys[] = $iterator->getSubIterator($step)->key();
            }
            $result[ implode('.', $keys) ] = $value;
        }

        return $result;
    }
}