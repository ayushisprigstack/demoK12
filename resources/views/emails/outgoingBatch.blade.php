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
    <p>I hope this email finds you well. I am writing to inform you that an outgoing batch has been created Successfully. As part of our proactive approach to communication, I am providing you with the details of the batch.</p>
    <p>Batch Details</p>
    <p>Batch Name: {{$batchname}}</p>
    <p>Batch Notes: {{$batchnotes}}</p>
    <p>Number of Tickets: {{$totaltickets}}</p>
    <p>Thanks!</p>
    <p>{{$school_name}}</p>
</div>
