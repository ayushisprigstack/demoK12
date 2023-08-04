<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class SchoolToAdminMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     *
     * @param $user
     */
    public function __construct(array $data)
    {

        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
{        
    return $this->view('schoolToAdmin')
        ->from('info@k12techrepairs.com')
        ->subject('Payment info added!')
        ->with(['invoiceId' => $this->data['invoiceId'],            
            'chequeNo'=>$this->data['chequeNo'],
            'batchName'=>$this->data['batchName'],
            'schoolName'=>$this->data['schoolName']
            ]);
}
}
