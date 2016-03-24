<?php

namespace Mesour\Sources\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class User
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
	 *
	 * @ORM\Column(name="action", type="integer", nullable=true)
	 */
	private $action;

	/**
	 * @var string
	 * @ORM\Column(name="role", type="enum", columnDefinition="enum('admin', 'moderator')", nullable=false)
	 */
	private $role;

	/**
	 * @var string
	 * @ORM\Column(name="name", type="string", length=64, nullable=true)
	 */
	private $name;

	/**
	 * @var string
	 * @ORM\Column(name="surname", type="string", length=64, nullable=false)
	 */
	private $surname;

	/**
	 * @var string
	 * @ORM\Column(name="email", type="string", length=64, nullable=false)
	 */
	private $email;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="last_login", type="datetime", nullable=true)
	 */
	private $lastLogin;

	/**
	 * @var int
	 * @ORM\Column(name="amount", type="integer", nullable=true)
	 */
	private $amount;

	/**
	 * @var int
	 * @ORM\Column(name="order", type="integer", nullable=false)
	 */
	private $order;

	/**
	 * @var string
	 * @ORM\Column(name="avatar", type="string", length=128, nullable=false)
	 */
	private $avatar;

	/**
	 * @var integer
	 * @ORM\Column(name="timestamp", type="integer", nullable=true)
	 */
	private $timestamp;

	/**
	 * @var bool
	 * @ORM\Column(name="has_pro", type="boolean", nullable=false)
	 */
	private $hasPro;

	/**
	 * @var Group
	 * @ORM\OneToOne(targetEntity="Group", mappedBy="user")
	 * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
	 */
	private $group;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="Mesour\Sources\Tests\Entity\UserAddress", mappedBy="user")
	 */
	private $addresses;

	/**
	 * @var ArrayCollection
	 * @ORM\ManyToMany(targetEntity="Mesour\Sources\Tests\Entity\Company", inversedBy="users")
	 * @ORM\JoinTable(
	 *     name="user_companies",
	 *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")}
	 * )
	 */
	private $companies;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	public function toArray()
	{
		$addresses = [];
		foreach ($this->addresses as $address) {
			/** @var UserAddress $address */
			$addresses[] = $address->toArray();
		}
		$companies = [];
		foreach ($this->companies as $company) {
			/** @var Company $company */
			$companies[] = $company->toArray();
		}
		$group = $this->group->toArray();
		return [
			'id' => $this->id,
			'action' => $this->action,
			'name' => $this->name,
			'surname' => $this->surname,
			'amount' => $this->amount,
			'email' => $this->email,
			'avatar' => $this->avatar,
			'order' => $this->order,
			'timestamp' => $this->timestamp,
			'last_login' => $this->lastLogin,
			'role' => $this->role,
			'has_pro' => (bool) $this->hasPro,
			'group_name' => $group['name'],
			'group_type' => $group['type'],
			'group_date' => $group['date'],
			'addresses' => $addresses,
			'companies' => $companies,
			'group' => $group,
		];
	}

}
