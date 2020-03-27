<?php

namespace ImageMap;

use Doctrine\Common\Collections\Criteria;
use Omeka\Entity\Item;
use Omeka\Entity\Media;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Omeka\File\Store\Local;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator) {
        $conn = $serviceLocator->get('Omeka\Connection');

        $conn->exec('CREATE TABLE image_map_cords (id INT PRIMARY KEY AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, cords VARCHAR(255) NOT NULL, image_width INT NOT NULL, image_height INT NOT NULL, CONSTRAINT image_map_cords_unique UNIQUE (item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE image_map_target (id INT PRIMARY KEY AUTO_INCREMENT NOT NULL, target VARCHAR(255) NOT NULL, cords_id INT NOT NULL, INDEX cords_ind (cords_id), FOREIGN KEY (cords_id) REFERENCES image_map_cords(id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator) {
        $conn = $serviceLocator->get('Omeka\Connection');

        $conn->exec('DROP TABLE IF EXISTS image_map_target;');
        $conn->exec('DROP TABLE IF EXISTS image_map_cords;');
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
