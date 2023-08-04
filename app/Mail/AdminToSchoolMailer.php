<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class AdminToSchoolMailer extends Mailable
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
//        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
{
  
    return $this->view('adminToSchool')
        ->from('info@k12techrepairs.com')
        ->subject('Payment Approved')
        ->with(['batchId' => $this->data['batchId'],
            'batchName' => $this->data['batchName'],
            'schoolName'=>$this->data['schoolName'],
            'receipt'=>$this->data['receipt'],
            'invoiceId'=>$this->data['invoiceId'],
            'notes'=>$this->data['notes']
                ]);
}
}
