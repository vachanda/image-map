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
     * @Column(nullable=false)
     */
    protected $coordinates;

    /**
     * @Column(nullable=false)
     */
    protected $target;

    /**
     * @Column(nullable=false)
     */
    protected $alt;


    public function getId() {
        return $this->id;
    }

    public function setItem(Item $item) {
        $this->item = $item;
    }

    public function getItem() {
        return $this->item;
    }

    public function setCoordinates($coordinates) {
        $this->coordinates = $coordinates;
    }

    public function getCoordinates() {
        return $this->coordinates;
    }

    public function setTarget($target) {
        $this->target = $target;
    }

    public function getTarget() {
        return $this->target;
    }

    public function setAlt($alt) {
        $this->alt = $alt;
    }

    public function getAlt() {
        return $this->alt;
    }
}