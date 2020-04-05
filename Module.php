<?php

namespace ImageMap;

use Doctrine\ORM\Events;
use ImageMap\Db\Event\Listener\DetachOrphanMappings;
use Omeka\Module\AbstractModule;
use Omeka\Permissions\Acl;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event) {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        $acl->allow(
            [Acl::ROLE_AUTHOR,
                Acl::ROLE_EDITOR,
                Acl::ROLE_GLOBAL_ADMIN,
                Acl::ROLE_REVIEWER,
                Acl::ROLE_SITE_ADMIN,
            ],
            ['ImageMap\Api\Adapter\ImageMapAdapter',
             'ImageMap\Entity\ImageMap',
            ]
        );

        $acl->allow(
            null,
            ['ImageMap\Api\Adapter\ImageMapAdapter',
                'ImageMap\Entity\ImageMap'
            ],
            ['show', 'browse', 'read', 'search']
            );

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $em->getEventManager()->addEventListener(
            Events::preFlush,
            new DetachOrphanMappings
        );
    }

    public function install(ServiceLocatorInterface $serviceLocator) {
        $conn = $serviceLocator->get('Omeka\Connection');

        $conn->exec('CREATE TABLE image_map (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, coordinates VARCHAR(255) NOT NULL, target VARCHAR(255) NOT NULL, alt VARCHAR(255) NOT NULL, INDEX IDX_CB477AE8126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE image_map ADD CONSTRAINT FK_CB477AE8126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator) {
        $conn = $serviceLocator->get('Omeka\Connection');

        $conn->exec('DROP TABLE IF EXISTS image_map;');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager) {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            [$this, 'addImageMapTab']
        );

        // Add the map form to the item add and edit pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            [$this, 'handleViewFormAfter']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleImageMap']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemRepresentation',
            'rep.resource.json',
            [$this, 'filterItemJsonLd']
        );
    }

    public function handleViewFormAfter(Event $event) {
        echo $event->getTarget()->partial('imagemap/index/form');
    }

    public function addImageMapTab(Event $event) {
        $view = $event->getTarget();
        $sectionNavs = $event->getParam('section_nav');
        $sectionNavs['image-map'] = $view->translate('Image Map');
        $event->setParam('section_nav', $sectionNavs);
    }

    /**
     * Add the mapping and marker data to the item JSON-LD.
     *
     * Event $event
     */
    public function filterItemJsonLd(Event $event)
    {
        $item = $event->getTarget();
        $jsonLd = $event->getParam('jsonLd');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');

        // Add marker data.
        $response = $api->search('image_maps', ['item_id' => $item->id()]);
        foreach ($response->getContent() as $marker) {
            // There's zero or more markers per item.
            $jsonLd['o-module-image-map:map'][] = $marker->getReference();
        }

        $event->setParam('jsonLd', $jsonLd);
    }


    /**
     * Handle hydration for image map data.
     *
     * @param Event $event
     */
    public function handleImageMap(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $request = $event->getParam('request');

        if (!$itemAdapter->shouldHydrate($request, 'o-module-image-map:map')) {
            return;
        }

        $item = $event->getParam('entity');
        $entityManager = $itemAdapter->getEntityManager();
        $imageMapAdapter = $itemAdapter->getAdapter('image_maps');
        $retainMapIds = [];

        // Create/update Image Maps passed in the request.
        foreach ($request->getValue('o-module-image-map:map', []) as $mapData) {
            if (isset($mapData['o:id'])) {
                $subRequest = new \Omeka\Api\Request('update', 'image_maps');
                $subRequest->setId($mapData['o:id']);
                $subRequest->setContent($mapData);
                $imageMap = $imageMapAdapter->findEntity($mapData['o:id'], $subRequest);
                $imageMapAdapter->hydrateEntity($subRequest, $imageMap, new \Omeka\Stdlib\ErrorStore);
                $retainMapIds[] = $imageMap->getId();
            } else {
                $subRequest = new \Omeka\Api\Request('create', 'image_maps');
                $subRequest->setContent($mapData);
                $imageMap = new \ImageMap\Entity\ImageMap;
                $imageMap->setItem($item);
                $imageMapAdapter->hydrateEntity($subRequest, $imageMap, new \Omeka\Stdlib\ErrorStore);
                $entityManager->persist($imageMap);
            }
        }

        // Delete existing maps not passed in the request.
        $existingMaps = [];
        if ($item->getId()) {
            $dql = 'SELECT mm FROM ImageMap\Entity\ImageMap mm INDEX BY mm.id WHERE mm.item = ?1';
            $query = $entityManager->createQuery($dql)->setParameter(1, $item->getId());
            $existingMaps = $query->getResult();
        }
        foreach ($existingMaps as $existingMapId => $existingMap) {
            if (!in_array($existingMapId, $retainMapIds)) {
                $entityManager->remove($existingMap);
            }
        }
    }
}
