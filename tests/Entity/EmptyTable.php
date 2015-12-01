<?php

namespace Mesour\Sources\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mesour\Sources\Tests\Entity\Empty
 *
 * @ORM\Table(name="empty")
 * @ORM\Entity
 */
class EmptyTable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="empty_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $emptyId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="surname", type="string", length=64, nullable=false)
     */
    private $surname;


    /**
     * Get emptyId
     *
     * @return integer
     */
    public function getEmptyId()
    {
        return $this->emptyId;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return EmptyTable
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set surname
     *
     * @param string $surname
     *
     * @return EmptyTable
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * Get surname
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->surname;
    }
}

