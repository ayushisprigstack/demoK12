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
    <p>
        Congratulations! 
        We have assigned you the access role of
        {{ $access_type }}.
    <p>To proceed, please log in to K-12 Tech Platform using this
        {{ $email }}.</p>
    Please don't hesitate to reach out to us if you encounter any difficulties. We are here to assist you.
</p>
<p> Thanks!</p>
</div>

