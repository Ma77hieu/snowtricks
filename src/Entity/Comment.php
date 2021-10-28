<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 */
class Comment
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
    private $CommentText;

    /**
     * @ORM\Column(type="datetime")
     */
    private $CreationDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $ModificationDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentText(): ?string
    {
        return $this->CommentText;
    }

    public function setCommentText(string $CommentText): self
    {
        $this->CommentText = $CommentText;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->CreationDate;
    }

    public function setCreationDate(\DateTimeInterface $CreationDate): self
    {
        $this->CreationDate = $CreationDate;

        return $this;
    }

    public function getModificationDate(): ?\DateTimeInterface
    {
        return $this->ModificationDate;
    }

    public function setModificationDate(?\DateTimeInterface $ModificationDate): self
    {
        $this->ModificationDate = $ModificationDate;

        return $this;
    }
}
