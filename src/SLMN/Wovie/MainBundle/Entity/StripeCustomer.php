<?php
namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="stripecustomer")
 */
class StripeCustomer
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Sekl\Main\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="createdBy", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $customerId;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $paidUntil;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $delinquent = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $chargeFailureMessage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $receiptInfo;

    /**
     * @return mixed
     */
    public function getReceiptInfo()
    {
        return $this->receiptInfo;
    }

    /**
     * @param mixed $receiptInfo
     */
    public function setReceiptInfo($receiptInfo)
    {
        $this->receiptInfo = $receiptInfo;
    }

    /**
     * @return mixed
     */
    public function getChargeFailureMessage()
    {
        return $this->chargeFailureMessage;
    }

    /**
     * @param mixed $chargeFailureMessage
     */
    public function setChargeFailureMessage($chargeFailureMessage)
    {
        $this->chargeFailureMessage = $chargeFailureMessage;
    }

    /**
     * @return mixed
     */
    public function getDelinquent()
    {
        return $this->delinquent;
    }

    /**
     * @param mixed $delinquent
     */
    public function setDelinquent($delinquent)
    {
        $this->delinquent = $delinquent;
    }


    /**
     * @return mixed
     */
    public function getPaidUntil()
    {
        return $this->paidUntil;
    }

    /**
     * @param mixed $paidUntil
     */
    public function setPaidUntil($paidUntil)
    {
        $this->paidUntil = $paidUntil;
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
} 