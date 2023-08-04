<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class InvoiceMailer extends Mailable
{
    use Queueable, SerializesModels;

   public $pdfPath;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($pdfPath)
    {
        $this->pdfPath = $pdfPath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {           
        $contents = file_get_contents($this->pdfPath);

        return $this->view('invoice')
                    ->from('info@k12techrepairs.com')
                    ->subject('New Invoice generated')
                    ->attachData($contents, 'invoice.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}
