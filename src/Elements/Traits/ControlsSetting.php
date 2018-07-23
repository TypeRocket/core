<?php

namespace TypeRocket\Elements\Traits;

trait ControlsSetting {

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

}