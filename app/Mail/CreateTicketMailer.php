<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class CreateTicketMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
    public function build()
{
  
    return $this->view('emails.createTicketMail')
                        ->from('info@k12techrepairs.com')
                        ->subject('New ticket created!')
                        ->with(['name' => $this->data['name'],
                            'school_name' => $this->data['school_name'],
                            'device' => $this->data['device'],
                            'ticketnum' => $this->data['ticketnum'],
                            'ticketnotes' => $this->data['ticketnote'],
                            'createdat' => $this->data['ticketcreateddate'],
        ]);
    }
}
