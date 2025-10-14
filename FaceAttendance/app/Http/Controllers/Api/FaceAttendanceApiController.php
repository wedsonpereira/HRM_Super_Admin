<?php

namespace Modules\FaceAttendance\app\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\FaceAttendance\app\Models\FaceData;

class FaceAttendanceApiController extends Controller
{
    // Alias for mobile app compatibility
    public function getFaceData()
    {
        return $this->getFaceDataAsImage();
    }
    public function getFaceDataAsImage()
    {
      $faceData = FaceData::where('user_id', auth()->id())->first();

      if(!$faceData) {
        return Error::response('Face data not found');
      }

      $response = [
        'imageUrl' => tenant_asset('face_data/' . $faceData->face_data_image),
        'landmarks' => $faceData->face_data,
      ];

      return Success::response($response);
    }

  public function isFaceDataAdded()
  {
    $faceData = FaceData::where('user_id', auth()->id())->first();

    if(!$faceData) {
      return Error::response('Face data not found');
    }

    return Success::response('Face data found');
  }

  public function addOrUpdateFaceData(Request $request)
  {
    $validated = $request->validate([
      'landmarks' => 'required',
      'file' => 'required|image',
    ]);

    $faceData = FaceData::where('user_id', auth()->id())->first();

    if($faceData) {
      $faceData->face_data = $validated['landmarks'];
      $faceData->face_data_image = $this->storeFaceDataImage($validated['file']);
      $faceData->save();
    } else {
      $faceData = new FaceData();
      $faceData->user_id = auth()->id();
      $faceData->face_data_image = $this->storeFaceDataImage($validated['file']);
      $faceData->face_data = $validated['landmarks'];
      $faceData->save();
    }

    return Success::response('Face data added');
  }

  private function storeFaceDataImage($image)
  {
    $imageName = time() . '.' . $image->extension();

    Storage::disk('public')->putFileAs('face_data/', $image, $imageName);

    return $imageName;
  }
}
