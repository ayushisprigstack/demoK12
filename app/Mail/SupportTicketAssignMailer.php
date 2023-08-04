<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class SupportTicketAssignMailer extends Mailable
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
  
    return $this->view('emails.SupportTicketAssign')
                        ->from('info@k12techrepairs.com')
                        ->subject('K12TechRepairs - New support ticket is assigned to you')
                        ->with(['SchoolName' => $this->data['SchoolName'],
                            'createdBy' => $this->data['CreatedBy'],
                            'name' => $this->data['Name'],
                            'ticketNum' => $this->data['TicketNum'],
                            'title' => $this->data['Title'],
                            'discription' => $this->data['Discription'],
                            'link' => $this->data['Link']
        ]);
    }
}
