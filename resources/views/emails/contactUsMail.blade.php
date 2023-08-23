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
    <h3>Hello {{$name}}, </h3>
</div>
<div id='lower-side'>
    <p>We would like to inform you that a new ticket has been created for {{$device}} from {{$school_name}}. Please
        review the details and take the necessary action accordingly.</p>
    <p>Ticket Number: {{$ticketnum}}</p>
    <p>Ticket Notes: {{$ticketnotes}}</p>
    <p>Created Date: {{$createdat}}</p>
    <p> Thanks!</p>
    <p>{{$school_name}}</p>
</div>
