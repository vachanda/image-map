<?php
namespace ImageMap\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\Http\Response;

class EditController extends AbstractActionController {
    public function createAction() {
        $result = "";
        $this->logger()->debug("Testing");

        if ($this->getRequest()->isPost()) {
            $data = \Zend\Json\Json::decode($this->getRequest()->getContent());

            $result = new JsonModel(array(
                'data' => $data,
                'success' => true,
            ));
        } else {
            $this->response->setStatusCode(Response::STATUS_CODE_405);
            $result = new JsonModel(array(
                'message' => 'only POST requests accepted',
                'success' => false,
            ));
        }

        return $result;
    }
}

?>