<?php


namespace App\Entity;


class Password
{

    private $oldPass;

    private $newPass;

    private $confirmPass;

    /**
     * @return mixed
     */
    public function getOldPass()
    {
        return $this->oldPass;
    }

    /**
     * @param mixed $oldPass
     */
    public function setOldPass($oldPass): void
    {
        $this->oldPass = $oldPass;
    }

    /**
     * @return mixed
     */
    public function getNewPass()
    {
        return $this->newPass;
    }

    /**
     * @param mixed $newPass
     */
    public function setNewPass($newPass): void
    {
        $this->newPass = $newPass;
    }

    /**
     * @return mixed
     */
    public function getConfirmPass()
    {
        return $this->confirmPass;
    }

    /**
     * @param mixed $confirmPass
     */
    public function setConfirmPass($confirmPass): void
    {
        $this->confirmPass = $confirmPass;
    }


}