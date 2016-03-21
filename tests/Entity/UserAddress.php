<?php

namespace Mesour\Sources\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_addresses")
 * @ORM\Entity
 */
class UserAddress
{

	/**
	 * @var int
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(name="street", type="string", length=255, nullable=false)
	 */
	private $street;

	/**
	 * @var string
	 * @ORM\Column(name="city", type="string", length=255, nullable=false)
	 */
	private $city;

	/**
	 * @var string
	 * @ORM\Column(name="zip", type="string", length=255, nullable=false)
	 */
	private $zip;

	/**
	 * @var string
	 * @ORM\Column(name="country", type="string", length=255, nullable=false)
	 */
	private $country;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="addresses")
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

	public function toArray()
	{
		return [
			'id' => $this->id,
			'user_id' => $this->user->getId(),
			'street' => $this->street,
			'city' => $this->city,
			'zip' => $this->zip,
			'country' => $this->country,
		];
	}

}

