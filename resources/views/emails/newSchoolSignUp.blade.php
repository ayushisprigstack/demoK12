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

#message { margin-top: -.5em; color: #757575; 
/*           letter-spacing: 1px; */
}
#text{
 
   font-size: 1em;  
}
#contBtn { position: relative; top: 1.5em; text-decoration: none; background: #2cc3a9; color: #fff; margin: auto; padding: .8em 3em; -webkit-box-shadow: 0px 15px 30px rgba(50, 50, 50, 0.21); -moz-box-shadow: 0px 15px 30px rgba(50, 50, 50, 0.21); box-shadow: 0px 15px 30px rgba(50, 50, 50, 0.21); border-radius: 25px; -webkit-transition: all .4s ease; -moz-transition: all .4s ease; -o-transition: all .4s ease; transition: all .4s ease; } #contBtn:hover { -webkit-box-shadow: 0px 15px 30px rgba(60, 60, 60, 0.40); -moz-box-shadow: 0px 15px 30px rgba(60, 60, 60, 0.40); box-shadow: 0px 15px 30px rgba(60, 60, 60, 0.40); -webkit-transition: all .4s ease; -moz-transition: all .4s ease; -o-transition: all .4s ease; transition: all .4s ease; }
</style>

    <div id='lower-side'>  
        <h3 id="status" style="margin-top: 10px;text-align:left;">Successfully Registered </h3>
        <p id='message' style="text-align: left">Kindly click on the login with {{$email}} to proceed!</p>
        <div id='text' style="margin-top:50px;">
            <p style="margin-bottom:0px;"> Thank you for registering!</p> 
            <p style="margin-top:5px;">Happy working!</p> 
        </div>
    </div>
       
