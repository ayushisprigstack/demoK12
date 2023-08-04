<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class SupportTicketCreatedMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $bladeFile;
    /**
     * Create a new message instance.
     *
     * @param $user
     */
    public function __construct(array $data,$bladeFile)
    {
//        $this->user = $user;
        $this->data = $data;
        $this->bladeFile = $bladeFile;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
{
  
    return $this->view($this->bladeFile)
        ->from('info@k12techrepairs.com')
        ->subject(' K12TechRepairs - New support ticket received')
        ->with(['SchoolName' => $this->data['SchoolName'],
                            'createdBy' => $this->data['CreatedBy'],
                            'name' => $this->data['Name'],
                            'title' => $this->data['Title'],
            'link'=>$this->data['Link']
        ]);
    }
}
