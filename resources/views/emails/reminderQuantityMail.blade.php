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
       <h3>Hello {{$school_name}},</h3>
    </div> 
<div id='lower-side'>        
    <p>Kindly Check {{$partname}} quantity.</p>       
    <p>We would like to inform you that we have conducted a check on the available quantity of {{ $partname }}.
        Currently, we have {{ $remaining_quantity }} quantity of {{ $partname }} in our stock.</p>
    <p>If you have any further inquiries or require any assistance, please feel free to reach out to us. We are here to
        support you and ensure a smooth experience.</p>    
</div>
       
