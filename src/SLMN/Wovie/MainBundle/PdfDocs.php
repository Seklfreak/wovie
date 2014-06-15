<?php
/**
 * Created by IntelliJ IDEA.
 * User: sekl
 * Date: 14.06.14
 * Time: 11:02
 */

namespace SLMN\Wovie\MainBundle;


class PdfDocs
{
    protected $kernel;
    protected $em;

    protected $currentDoc = null;

    public function __construct($kernel, $em)
    {
        $this->kernel = $kernel;
        $this->em = $em;
    }

    protected function initDoc($title)
    {
        $this->currentDoc = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->currentDoc->SetCreator('WOVIE/'.$this->kernel->getEnvironment());
        $this->currentDoc->SetTitle($title);
        $this->currentDoc->setPrintHeader(false);
        $this->currentDoc->setPrintFooter(false);
        $this->currentDoc->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->currentDoc->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->currentDoc->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->currentDoc->SetFont('helvetica', '', 12);
    }

    public function generateReceipt($invoice)
    {
        $amount = $invoice->getAmount();
        if ($amount > 0)
        {
            $amount = substr($amount, null, -2).'.'.substr($amount, -2);
        }
        else
        {
            $amount = '0.00';
        }
        $receiptInfo = null;
        $stripeCustomersRepo = $this->em->getRepository('SLMNWovieMainBundle:StripeCustomer');
        $stripeCustomer = $stripeCustomersRepo->findOneByUser($invoice->getUser());
        if ($stripeCustomer && $stripeCustomer->getReceiptInfo())
        {
            $receiptInfo = $stripeCustomer->getReceiptInfo()."\n";
        }

        $this->initDoc('WovieApp.com receipt from '.$invoice->getDate()->format('Y-m-d'));
        $this->currentDoc->AddPage();
        $html = '
        <table>
    <tr>
        <td width="75%">
            <h1>Receipt</h1>
            <p>Invoice ID: '.$invoice->getInvoiceId().'<br><br><b>Account</b><br>'.nl2br(htmlspecialchars($receiptInfo)).$invoice->getUser()->getEmail().'</p>
            <h3></h3>
        </td>
        <td width="25%">
            <p style="text-align: right;">
                SLMN<br>
                Friedhofstraße 11<br>
                63791 Karlstein<br>
                GERMANY
            </p>
        </td>
    </tr>
</table>
<table border="1">
    <tr>
        <td width="75%"> WovieApp.com Monthly Subscription '.$invoice->getDate()->format('Y-m-d').'</td>
        <td width="25%"> USD $'.$amount.'</td>
    </tr>
</table>
';
        $this->currentDoc->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        return true;
    }

    public function getBody()
    {
        return $this->currentDoc->Output('wovie.pdf', 'S');
    }
}
