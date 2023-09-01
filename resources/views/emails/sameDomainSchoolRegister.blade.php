<style>
    body {}

    #upper-side {
        padding: 2em 2em 0em 2em;
    }

    #lower-side {
        padding: 0em 2em 0em 2em;
    }
</style>
<div id='upper-side'>
    <h3>New School Registered</h3>
</div>
<div id='lower-side'>
    <p>We are pleased to inform you that a new school has registered with the domain {{ $domain }}. However, we noticed that the domain {{ $domain }} already have a school.</p>
    <p>New School Details:<br />School Name:{{$school_name }} <br />Domain: {{$domain}}</p>
    <p>As the administrator, we kindly request you to review this registration and take the necessary actions to onboard
        the new school. This includes verifying their details, setting up their account, and providing them with the
        required access and resources.</p> 
        <p>if you want to approve the school click on the {{$link}}</p>   
    <p>If you have any questions or require any assistance during the on boarding process, please feel free to reach out
        to us. We are here to support you every step of the way.</p>
    <p>Thanks!</p>
    <p>K12 Tech</p>
</div>
