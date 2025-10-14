@php
    $faceData = \Modules\FaceAttendance\app\Models\FaceData::where('user_id', $user->id)->first();
@endphp
<!-- Face Attendance Section -->
<div class="card mt-3">
  <div class="card-body text-center">
    <h5 class="card-title mb-4">
      <i class="bx bx-face text-muted"></i> Face Attendance
    </h5>

    <!-- Check if Face Data is available -->
    @if(isset($faceData))
      <div class="d-flex justify-content-center">
        <img src="{{tenant_asset('face_data/'.$faceData->face_data_image)}}"
             alt="Face Data" class="rounded-circle border shadow-sm" width="180">
      </div>

      <div class="mt-3">
        <form action="{{ route('faceAttendance.deleteFaceData',$faceData->user_id) }}" method="POST" class="d-inline-block">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove face data?')">
            <i class="bx bx-trash"></i> Remove Face Data
          </button>
        </form>
        <button class="btn btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#updateFaceDataModal">
          <i class="bx bx-refresh"></i> Update Face Data
        </button>
      </div>

    @else
      <p class="text-muted">No face data found. Please inform employee to add face data.</p>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFaceDataModal">
        <i class="bx bx-plus"></i> Add Face Data
      </button>
    @endif


    <!-- Helper Text -->
    @if($settings->is_helper_text_enabled)
      <div class="alert alert-primary alert-dismissible text-start mt-4" role="alert">
        <h6 class="alert-heading">Note</h6>
        <p class="mb-0">Face data is used for attendance verification. Please upload a clear image of the employee's face.</p>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
      </div>
    @endif


  </div>
</div>

<!-- Add Face Data Modal -->
<div class="modal fade" id="addFaceDataModal" tabindex="-1" aria-labelledby="addFaceDataModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{route('faceAttendance.addOrUpdateFaceData')}}" method="POST" enctype="multipart/form-data">
      <input type="hidden" id="user_id" name="user_id" value="{{ $user->id }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bx bx-plus-circle"></i> Add Face Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="file" name="face_data" class="form-control" required>
          <small class="text-muted">Upload a clear image of the employee's face.</small>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Update Face Data Modal -->
<div class="modal fade" id="updateFaceDataModal" tabindex="-1" aria-labelledby="updateFaceDataModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{route('faceAttendance.addOrUpdateFaceData')}}" method="POST" enctype="multipart/form-data">
      <input type="hidden" id="user_id" name="user_id" value="{{ $user->id }}">
      @csrf
      @method('POST')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bx bx-refresh"></i> Update Face Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="file" name="face_data" class="form-control" required>
          <small class="text-muted">Upload a new image to replace the current face data.</small>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
