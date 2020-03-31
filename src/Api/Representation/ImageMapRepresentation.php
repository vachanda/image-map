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
	        'o-module-mapping:coordinates' => $this->coordinates(),
	        'o-module-mapping:target' => $this->target(),
	        'o-module-mapping:alt' => $this->alt(),
	    ];
	}

	public function item() {
	    return $this->getAdapter('items')
	        ->getRepresentation($this->resource->getItem());
	}

	public function coordinates() {
	    return $this->resource->getCoordinates();
	}

	public function target() {
	    return $this->resource->getTarget();
	}

	public function alt() {
	    return $this->resource->getAlt();
	}
}

?>