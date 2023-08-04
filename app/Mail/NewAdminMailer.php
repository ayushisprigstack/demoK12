<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class NewAdminMailer extends Mailable
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
  
    return $this->view('emails.newAdmin')
        ->from('info@k12techrepairs.com')
        ->subject('New Admin')
        ->with(['name' => $this->data['name'],
            'email' => $this->data['email'],
            'school_name'=>$this->data['school_name']]);
}
}
