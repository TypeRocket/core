<?php
namespace TypeRocket\Elements\Traits;

trait ControlsSetting
{
    protected $hide = [
        'clone' => false,
        'contract' => false,
        'move' => false,
        'append' => false,
        'remove' => false,
        'flip' => false,
        'clear' => false,
    ];

    /**
     * Hide Append
     *
     * @return $this
     */
    public function hideAppend()
    {
        $this->hide['append'] = true;
        return $this;
    }

    /**
     * Show Append
     *
     * @return $this
     */
    public function showAppend()
    {
        $this->hide['append'] = false;
        return $this;
    }

    /**
     * Hide Clone Control
     *
     * @return $this
     */
    public function hideClone()
    {
        $this->hide['clone'] = true;
        return $this;
    }

    /**
     * Show Clone Control
     *
     * @return $this
     */
    public function showClone()
    {
        $this->hide['clone'] = false;
        return $this;
    }

    /**
     * Hide Clone Control
     *
     * @return $this
     */
    public function hideMove()
    {
        $this->hide['move'] = true;
        return $this;
    }

    /**
     * Show Clone Control
     *
     * @return $this
     */
    public function showMove()
    {
        $this->hide['move'] = false;
        return $this;
    }

    /**
     * Hide Flip Control
     *
     * @return $this
     */
    public function hideFlip()
    {
        $this->hide['flip'] = true;
        return $this;
    }

    /**
     * Show Flip Control
     *
     * @return $this
     */
    public function showFlip()
    {
        $this->hide['flip'] = false;
        return $this;
    }

    /**
     * Hide Clear Control
     *
     * @return $this
     */
    public function hideClear()
    {
        $this->hide['clear'] = true;
        return $this;
    }

    /**
     * Show Clear Control
     *
     * @return $this
     */
    public function showClear()
    {
        $this->hide['clear'] = false;
        return $this;
    }

    /**
     * Hide Handle
     *
     * @return $this
     */
    public function hideContract()
    {
        $this->hide['contract'] = true;
        return $this;
    }

    /**
     * Show Handle
     *
     * @return $this
     */
    public function showContract()
    {
        $this->hide['contract'] = false;
        return $this;
    }

    /**
     * Set Controls settings
     *
     * @param array $controls options include: flip, clear, add, contract
     *
     * @return mixed
     */
    public function setControls( array $controls ) {
        return $this->setSetting('controls', $controls);
    }

    /**
     * Set Control Add
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlAdd( $value ) {
        return $this->appendToArraySetting('controls', 'add', $value);
    }

    /**
     * Set Control Flip
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlFlip( $value ) {
        return $this->appendToArraySetting('controls', 'flip', $value);
    }

    /**
     * Set Control Contract
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlContract( $value ) {
        return $this->appendToArraySetting('controls', 'contract', $value);
    }

    /**
     * Set Control Contract
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlExpand( $value ) {
        return $this->appendToArraySetting('controls', 'expand', $value);
    }

    /**
     * Set Control Clear
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlClear( $value ) {
        return $this->appendToArraySetting('controls', 'clear', $value);
    }

    /**
     * Make repeater contracted by default
     *
     * @return $this
     */
    public function contracted()
    {
        return $this->setSetting('contracted', true);
    }

}