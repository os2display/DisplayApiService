<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Interfaces\BlameableInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class BlameableListener
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof BlameableInterface) {
            /** @var User $user */
            $user = $this->security->getUser();

            if (null !== $user) {
                $entity->setCreatedBy($user->getEmail());
                $entity->setModifiedBy($user->getEmail());
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof BlameableInterface) {
            /** @var User $user */
            $user = $this->security->getUser();

            if (null !== $user) {
                $entity->setModifiedBy($user->getEmail());
            }
        }
    }
}
