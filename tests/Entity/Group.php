<?php

namespace Mesour\Sources\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="groups")
 * @ORM\Entity
 */
class Group
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
	 * @ORM\Column(name="type", type="enum", columnDefinition="enum('first', 'second')", nullable=false)
	 */
	private $type;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="date", type="datetime", nullable=false)
	 */
	private $date;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="members", type="integer", nullable=false)
	 */
	private $members = 0;

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
			'type' => $this->type,
			'date' => $this->date,
			'members' => $this->members,
		];
	}

}

