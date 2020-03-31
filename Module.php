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
            null,
            'ImageMap\Controller\Admin\Edit'
        );

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

        $conn->exec('CREATE TABLE image_map (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, coordinates VARCHAR(255) NOT NULL, target VARCHAR(255) NOT NULL, alt VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_CB477AE8126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
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
}
