<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\K12User;

class ContactUsMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
{
  
    return $this->view('emails.contactUsMail')
        ->from('info@k12techrepairs.com')
        ->subject('Welcome to K12!')
        ->with(['name' => $this->data['name'],'plancreateddate' => $this->data['plancreateddate'],'planname'=>$this->data['planname'],'plannum'=>$this->data['plannum'],'school_name'=>$this->data['school_name']]);
}
}
