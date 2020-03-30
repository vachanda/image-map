<?php
namespace ImageMap\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ImageMapAdapter extends AbstractEntityAdapter {
    public function getResourceName() {
        return 'image_map';
    }

    public function getRepresentationClass() {
        return 'ImageMap\Api\Representation\ImageMapRepresentation';
    }

    public function getEntityClass() {
        return 'ImageMap\Entity\ImageMap';
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore) {
        $data = $request->getContent();

        if (Request::CREATE === $request->getOperation()
            && isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }

        if ($this->shouldHydrate($request, 'o-module-image-map:cords')) {
            $entity->setCords($request->getValue('o-module-image-map:cords'));
        }
        if ($this->shouldHydrate($request, 'o-module-image-map:target')) {
            $entity->setTarget($request->getValue('o-module-image-map:target'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore) {
        
    }

    public function buildQuery(QueryBuilder $qb, array $query) {
        if (isset($query['item_id'])) {
            $items = $query['item_id'];
            if (!is_array($items)) {
                $items = [$items];
            }
            $items = array_filter($items, 'is_numeric');

            if ($items) {
                $itemAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.item', $itemAlias,
                    'WITH', $qb->expr()->in("$itemAlias.id", $this->createNamedParameter($qb, $items))
                );
            }
        }
    }
}

?>