<?php


namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 */
class Message
{
    use EntityFields;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50)
     */
    private ?string $user = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", length=10000)
     */
    private ?string $body = null;

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): void
    {
        $this->user = $user;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }
}