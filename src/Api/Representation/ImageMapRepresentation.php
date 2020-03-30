<?php
namespace ImageMap\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ImageMapRepresentation extends AbstractEntityRepresentation {
	public function getJsonLdType() {
	    return 'o-module-image-map:Map';
	}

	public function getJsonLd() {
	    return [
	        'o:item' => $this->item()->getReference(),
	        'o-module-mapping:cords' => $this->cords(),
	        'o-module-mapping:target' => $this->target(),
	    ];
	}

	public function item() {
	    return $this->getAdapter('items')
	        ->getRepresentation($this->resource->getItem());
	}

	public function cords() {
	    return $this->resource->getCords();
	}

	public function target() {
	    return $this->resource->getTarget();
	}
}

?>