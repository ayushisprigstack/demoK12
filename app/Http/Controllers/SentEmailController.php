<?php
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SendGrid\Mail\Mail;

class SentEmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        // Create a new SendGrid\Mail\Mail instance
        $email = new Mail();
        $email->setFrom("180770107548@socet.edu.in", "Sender Name");
        $email->setSubject("Test email from Laravel using SendGrid");
        $email->addTo("team.sprigstack@gmail.com", "Recipient Name");
        $email->addContent("text/plain", "Hello, this is a test email from Laravel using SendGrid!");
        
        // Send the email using SendGrid API
        $sendgrid = new SendGrid(env('SENDGRID_API_KEY'));
        try {
            $response = $sendgrid->send($email);
            // Handle the response
        } catch (Exception $e) {
            // Handle the exception
        }
        
        // Return a response to the user
        return response()->json(['message' => 'Email sent successfully']);
    }
}
