<?php

namespace App\Entity;

use App\Repository\OrdemRepository;
use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\String\u;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=OrdemRepository::class)
 * @UniqueEntity("nome")
 */
class Ordem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $nome;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = (string) u($nome)->trim()->lower()->title(true);

        return $this;
    }
}
