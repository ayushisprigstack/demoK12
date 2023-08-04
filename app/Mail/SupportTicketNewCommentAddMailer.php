<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class SupportTicketNewCommentAddMailer extends Mailable
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
                         ->subject('K12TechRepairs - New comment added to your support ticket')
                         ->with(['name' => $this->data['name'],
                            'device' => $this->data['ticketNum'],
                            'comment' => $this->data['comment'],
                            'link'=>$this->data['link'],
                            'schoolName'=>$this->data['schoolName'],
                             'createdBy'=>$this->data['createdBy'],
                             'linkWithoutGUID'=>$this->data['linkWithoutGUID'],
                            ]);
    }

}
