
  <form  method="POST" action="{{url('/upload') }}" enctype="multipart/form-data">
        {{csrf_field()}}
        <div class="form-group">
            <label>Upload file</label>
            <input type="file" name="upload_file" class="form-control">
        </div>
        <input type="submit" value="Upload Image" name="submit" class="form-control">
        </form>