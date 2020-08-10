<?php


namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * The basic field that every entity should have.
 */
trait EntityFields
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue()
     * @ORM\Id()
     */
    private ?int $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\Version()
     */
    private int $version = 0;

    public function getId(): int
    {
        return $this->id === null ? 0 : $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->getId() > 0;
    }

    /**
     * This should output a string that resembles the entity best.
     *
     * I strongly encourage you to override it.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUniqueId();
    }

    /**
     * Returns an actually unique name by combining the class name and the id.
     *
     * @return string
     */
    public function getUniqueId(): string
    {
        $shortClassName = substr(strrchr(get_class($this), '\\'), 1);
        $identifier = $this->getId() ?? ('#' . spl_object_id($this));
        return "$shortClassName:$identifier";
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}