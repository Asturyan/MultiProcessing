<?php
namespace MultiProcessing\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Tools\URL;
use Thelia\Core\Event\PdfEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Template\TemplateHelper;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\ConfigQuery;

/**
 * Class GenerateController
 * @package MultiProcessing\Controller
 * @author HubChannel <mlemarchand@hubchannel.fr>
 */
class GenerateController extends BaseAdminController
{

    public function indexAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ['multiprocessing'], AccessManager::UPDATE)) {
            return $response;
        }

        $order_ids = (array)explode(',', $this->getRequest()->get('cmd'));
        
        $html = $this->renderRaw(
            'multiprocessing',
            array(
                'orders_id' => implode(',', $order_ids)
            ),
            TemplateHelper::getInstance()->getActivePdfTemplate()
        );

        try {
            $pdfEvent = new PdfEvent($html);
            
            $this->dispatch(TheliaEvents::GENERATE_PDF, $pdfEvent);

            if ($pdfEvent->hasPdf()) {
                
                /*$orders = OrderQuery::create()->findPks($order_ids);  
                $status = OrderStatusQuery::create()->findOneByCode(OrderStatus::CODE_PROCESSING);
                
                foreach($orders as $order) {
                    $event = new OrderEvent($order);
                    $event->setStatus($status->getId());
                    
                    $this->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
                }*/
                
                return $this->pdfResponse($pdfEvent->getPdf(), 'orders');
            }

        } catch (\Exception $e) {
            die($e->getMessage());
            \Thelia\Log\Tlog::getInstance()->error(sprintf('error during generating pdf for order id(s) : %d with message "%s"', implode(',', $order_ids), $e->getMessage()));

        }
    }
} 