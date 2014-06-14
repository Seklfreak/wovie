<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function receiptAction($id)
    {
        $invoicesRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:Invoice');
        $invoice = $invoicesRepo->findOneByInvoiceId($id);
        if ($invoice)
        {
            if ($invoice->getUser() == $this->getUser())
            {
                $pdfDocs = $this->get('pdf_docs');
                $pdfDocs->generateReceipt($invoice);
                $response = new Response($pdfDocs->getBody());
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'attachment; filename="receipt-'.$invoice->getDate()->format('Y-m-d').'.pdf"');
                return $response;
            }
            else
            {
                throw $this->createAccessDeniedException('You cannot access this receipt!');
            }
        }
        else
        {
            throw $this->createNotFoundException('Receipt not found!');
        }
    }
} 