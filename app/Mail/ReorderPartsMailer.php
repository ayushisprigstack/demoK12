<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class ReorderPartsMailer extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
{
    $mailBuilder = $this->view('emails.reminderQuantityMail')
        ->from('info@k12techrepairs.com')
        ->subject('part quantity below reminder quantity')
        ->with([
            'partname' => $this->data['partname'],
            'remaining_quantity' => $this->data['remaining_quantity'],
            'school_name' => $this->data['school_name'],
        ]);
}
}
