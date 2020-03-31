<?php
namespace ImageMap\Db\Event\Listener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use ImageMap\Entity\ImageMap;

/**
 * Automatically detach mappings and markers that reference unknown items.
 */
class DetachOrphanMappings
{
    /**
     * Detach all ImageMap entities that reference Items not currently in the
     * entity manager.
     *
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $identityMap = $uow->getIdentityMap();

        if (isset($identityMap[ImageMap::class])) {
            foreach ($identityMap[ImageMap::class] as $mapping) {
                if (!$em->contains($mapping->getItem())) {
                    $em->detach($mapping);
                }
            }
        }
    }
}