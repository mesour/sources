<?php

namespace Mesour\Sources\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="companies")
 * @ORM\Entity
 */
class Company
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
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 */
	private $name;

	/**
	 * @var string
	 * @ORM\Column(name="reg_num", type="string", length=255, nullable=false)
	 */
	private $regNum;

	/**
	 * @var bool
	 * @ORM\Column(name="verified", type="boolean", length=255, nullable=false)
	 */
	private $isVerified;

	/**
	 * @var ArrayCollection
	 * @ORM\ManyToMany(targetEntity="User", mappedBy="companies")
	 */
	private $users;

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
			'name' => $this->name,
			'reg_num' => $this->regNum,
			'verified' => $this->isVerified,
		];
	}

}

