<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class SupportTicketClosedMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
{  
    return $this->view('emails.SupportTicketClosed')
        ->from('info@k12techrepairs.com')
        ->subject('K12TechRepairs - New support ticket is assigned to you')
        ->with(['schoolName' => $this->data['schoolName'],
                            'name' => $this->data['name'],
                            'ticketNum' => $this->data['ticketNum'],
                            'ticketTitle' => $this->data['ticketTitle'],
                            'link' => $this->data['link'],
                            'ticketDescription' => $this->data['ticketDescription']
        ]);
    }
    
}
