<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MediaRepository::class)
 */
class Media
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Url;

    /**
     * @ORM\Column(type="boolean")
     */
    private $IsMain;

    /**
     * @ORM\ManyToOne(targetEntity=MediaType::class, inversedBy="media")
     * @ORM\JoinColumn(nullable=false)
     */
    private $mediaType;

    /**
     * @ORM\ManyToOne(targetEntity=Trick::class, inversedBy="media")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trick;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->Url;
    }

    public function setUrl(string $Url): self
    {
        $this->Url = $Url;

        return $this;
    }

    public function getIsMain(): ?bool
    {
        return $this->IsMain;
    }

    public function setIsMain(bool $IsMain): self
    {
        $this->IsMain = $IsMain;

        return $this;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(?MediaType $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getTrick(): ?Trick
    {
        return $this->trick;
    }

    public function setTrick(?Trick $trick): self
    {
        $this->trick = $trick;

        return $this;
    }
}
