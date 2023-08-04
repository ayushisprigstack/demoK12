<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\K12User;

class AddK12UserMailer extends Mailable
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
  
    return $this->view('emails.addK12UserMail')
        ->from('info@k12techrepairs.com')
        ->subject('Welcome to K12!')
        ->with(['name' => $this->data['name'],'email' => $this->data['email'],'access_type'=>$this->data['accessType']]);
}
}
