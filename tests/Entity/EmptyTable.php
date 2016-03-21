<?php

namespace Mesour\Sources\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="empty")
 * @ORM\Entity
 */
class EmptyTable
{
	/**
	 * @var integer
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(name="name", type="string", length=64, nullable=false)
	 */
	private $name;

	/**
	 * @var string
	 * @ORM\Column(name="surname", type="string", length=64, nullable=false)
	 */
	private $surname;

	public function toArray() {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'surname' => $this->surname,
		];
	}

}

