<?php
namespace TypeRocket\Elements\Traits;

trait DisplayPermissions
{
    /**
     * @var null|string
     */
    protected $displayCapability = null;

    /**
     * @param string|null $capability
     *
     * @return $this
     */
    public function setDisplayCapability(?string $capability)
    {
        $this->displayCapability = $capability;

        return $this;
    }

    public function getDisplayCapability() : ?string
    {
        return $this->displayCapability;
    }

    /**
     * @return bool
     */
    public function canDisplay() : bool
    {
        return is_null($this->displayCapability) || current_user_can($this->displayCapability);
    }
}