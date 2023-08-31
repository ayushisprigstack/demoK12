<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SameDomainSchoolRegisterMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
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
//        dd($this->user);
  
    return $this->view($this->bladeFile)
//        ->to($this->user->email)
        ->from('info@k12techrepairs.com')
        ->subject('New School Signup Notification')
         ->with(['email' => $this->data['email'],'link'=>$this->data['link'] ,'name' => $this->data['name'],'school_name'=>$this->data['school_name'],'domain'=>$this->data['domain']]);
}
}
