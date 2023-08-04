<style>
body {  } 
#card { position: relative; top: 100px; width: 350px; display: block; margin: auto;  font-family: 'Source Sans Pro', sans-serif; }

 #upper-side {  
  padding: 2em;  
  
  display: block;  
   
  border-top-right-radius: 8px;  
  border-top-left-radius: 8px;  
 }  
 #checkmark {  
  font-weight: lighter;  
  fill: #fff;  
  margin: -3.5em auto auto 20px;  
 }  
 #status {  
  font-weight: lighter;  
  text-transform: uppercase;  
  letter-spacing: 2px;  
  font-size: 1em;  
  margin-top: -.2em;  
  margin-bottom: 0;  
 }  
 #lower-side {  
  padding: 2em 2em 1em 2em;  
 
  display: block;  
  border-bottom-right-radius: 8px;  
  border-bottom-left-radius: 8px;  
 }  

#message { margin-top: -.5em;  
/*           letter-spacing: 1px; */
}
#text{
 
   font-size: 1em;  
}
#contBtn { position: relative; top: 1.5em; text-decoration: none; background: #2cc3a9; color: #fff; margin: auto; padding: .8em 3em; -webkit-box-shadow: 0px 15px 30px rgba(50, 50, 50, 0.21); -moz-box-shadow: 0px 15px 30px rgba(50, 50, 50, 0.21); box-shadow: 0px 15px 30px rgba(50, 50, 50, 0.21); border-radius: 25px; -webkit-transition: all .4s ease; -moz-transition: all .4s ease; -o-transition: all .4s ease; transition: all .4s ease; } #contBtn:hover { -webkit-box-shadow: 0px 15px 30px rgba(60, 60, 60, 0.40); -moz-box-shadow: 0px 15px 30px rgba(60, 60, 60, 0.40); box-shadow: 0px 15px 30px rgba(60, 60, 60, 0.40); -webkit-transition: all .4s ease; -moz-transition: all .4s ease; -o-transition: all .4s ease; transition: all .4s ease; }
</style>

    <div id='lower-side'>   
        <h3 id="message" style="margin-top: 10px;text-align:left;">Hey {{$name}},</h3>
        <p id='message' style="text-align: left">New Comment Added on {{$device}}</p>
        <p id='message' style="text-align: left">Comment: {{$comment}}</p>
        <p id='message' style="text-align: left">You can view the ticket comments by clicking <a href="{{$linkWithoutGUID}}">here</a></p>
         <p id='message' style="text-align: left">If you are unable to access the link, you can copy and paste it into your web browser to view the ticket comments.({{$linkWithoutGUID}})</p>
        <div id='text' style="margin-top:10px;">            
            <p style="margin-bottom:0px;"> Thanks!</p> 
            <p style="margin-bottom:0px;">{{$schoolName}}</p>
            
        </div>
    </div>
       
