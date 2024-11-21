<?php

namespace App\Entity;

use App\Repository\MarcadorImagemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\String\u;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=MarcadorImagemRepository::class)
 */
class MarcadorImagem
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
     * @ORM\Column(type="string", length=255)
     */
    private $nome;

    /**
     * @Assert\Regex("/^#[0-9A-Fa-f]{6}$/")
     * @ORM\Column(type="string", length=7, nullable=true)
     */
    private $cor;

    /**
     * @ORM\ManyToMany(targetEntity=Multimidia::class, inversedBy="marcadorImagems")
     */
    private $multimidia;

    public function __construct()
    {
        $this->multimidia = new ArrayCollection();
    }

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

    public function getCor(): ?string
    {
        return $this->cor;
    }

    public function setCor(string $cor): self
    {
        if (!empty($cor)) {
            $this->cor = (string) u($cor)->trim()->lower();
        } else {
            $this->cor = null;
        }
        
        return $this;
    }

    /**
     * @return Collection<int, Multimidia>
     */
    public function getMultimidia(): Collection
    {
        return $this->multimidia;
    }

    public function addMultimidium(Multimidia $multimidium): self
    {
        if (!$this->multimidia->contains($multimidium)) {
            $this->multimidia[] = $multimidium;
        }

        return $this;
    }

    public function removeMultimidium(Multimidia $multimidium): self
    {
        $this->multimidia->removeElement($multimidium);

        return $this;
    }
}
