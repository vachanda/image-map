<?php
namespace ImageMap\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;

/**
 * Defines the default state of an item's map.
 *
 * @Entity
 */
class ImageMap extends AbstractEntity {
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $item;

    /**
     * @Column(type="string", nullable=false)
     */
    protected $cords;

    /**
     * @Column(type="string", nullable=false)
     */
    protected $target;


    public function getId() {
        return $this->id;
    }

    public function setItem(Item $item) {
        $this->item = $item;
    }

    public function getItem() {
        return $this->item;
    }

    public function setCords($cords) {
        $this->cords = $cords;
    }

    public function getCords() {
        return $this->cords;
    }

    public function setTarget($target) {
        $this->target = $target;
    }

    public function getTarget() {
        return $this->target;
    }
}