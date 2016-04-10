<?php

namespace Mesour\Sources\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="wallets")
 * @ORM\Entity
 */
class Wallet
{

	/**
	 * @var int
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @var integer
	 * @ORM\Column(name="user_id", type="integer", nullable=false)
	 */
	private $userId;

	/**
	 * @var int
	 * @ORM\Column(name="amount", type="float", nullable=false)
	 */
	private $amount;

	/**
	 * @var string
	 * @ORM\Column(name="currency", type="enum", columnDefinition="enum('CZK', 'EUR')", nullable=false)
	 */
	private $currency;

	/**
	 * @var User
	 * @ORM\OneToOne(targetEntity="User", mappedBy="wallet")
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
	 */
	private $user;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'user_id' => $this->user->getId(),
			'amount' => $this->amount,
			'currency' => $this->currency,
		];
	}

}
