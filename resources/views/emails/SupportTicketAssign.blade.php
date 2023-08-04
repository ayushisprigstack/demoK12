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
    <h3>Hello {{ $name }},</h3>
</div>
<div id='lower-side'>
    <p>We wanted to notify you that a new ticket has been assigned to you. Please take a moment to review the ticket
        details provided below:</p>
    <p>Ticket Number: {{ $ticketNum }}<br />Ticket Title: {{ $title }}<br />
        Ticket Description: {{ $discription }}.
    </p>
    <p>You can view the ticket details by clicking <a href="{{$link}}">here</a></p>
    <p>If you are unable to access the link, you can copy and paste it into your web browser to view the ticket comments.({{$link}})</p>      
    <p>Thanks,</p>
    <p>{{ $SchoolName }}</p>
</div>
