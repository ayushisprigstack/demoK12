<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class outgoingBatchMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
    public function build()
{
  
    return $this->view('emails.outgoingBatch')
                        ->from('info@k12techrepairs.com')
                        ->subject('New Outgoing Batch created!')
                        ->with(['name' => $this->data['name'],
                            'school_name' => $this->data['school_name'],
                            'batchname' => $this->data['batchname'],
                            'batchnotes' => $this->data['batchnotes'],
                            'totaltickets' => $this->data['totaltickets']]);
    }
}
