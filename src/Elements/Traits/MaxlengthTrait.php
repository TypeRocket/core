<?php
namespace TypeRocket\Elements\Traits;

use TypeRocket\Html\Html;

trait MaxlengthTrait
{

    /**
     * Set Maxlength
     *
     * @param int $length
     *
     * @return mixed
     */
    public function maxlength($length)
    {
        return $this->setAttribute('maxlength', $length);
    }

    /**
     * Get the max length for text type fields
     *
     * @param string $value
     * @param string $maxLength
     *
     * @return string|\TypeRocket\Html\Html
     */
    public function getMaxlength( $value, $maxLength )
    {
        $max = '';

        if ( $maxLength != null && $maxLength > 0) {
            $left = ( (int) $maxLength ) - mb_strlen( $value );
            $max = Html::p(['class' => 'tr-maxlength'], 'Characters left: ')->nest(Html::span($left));
        }

        return (string) $max;
    }

}