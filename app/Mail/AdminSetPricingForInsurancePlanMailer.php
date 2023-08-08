<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Student;

class AdminSetPricingForInsurancePlanMailer extends Mailable {

    use Queueable,
        SerializesModels;

    public $user;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function build() {

        return $this->view('emails.adminSetPricingForInsurancePlan')
                        ->from('info@k12techrepairs.com')
                        ->subject('New Plan created!')
                        ->with(['name' => $this->data['name'],
                            'school_name' => $this->data['school_name'],
                            'plannum' => $this->data['plannum'],
                            'planname' => $this->data['planname'],
                            'plancreateddate' => $this->data['plancreateddate']]);
    }

}
