<?php

namespace Modules\FaceAttendance\app\Http\Controllers;

use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\FaceAttendance\app\Models\FaceData;

class FaceAttendanceController extends Controller
{
  private string $prefix = 'faceattendance::';

  public function getFaceDataAjax($userId)
  {
    $faceData = FaceData::where('user_id', $userId)->first();

    return Success::response($faceData);
  }

  public function addOrUpdateFaceData(Request $request)
  {
    $request->validate([
      'face_data' => 'required|file|mimes:jpg,jpeg,png|max:2048',
      'user_id' => 'required|exists:users,id',
    ]);

    $userId = $request->user_id;

    $faceData = FaceData::where('user_id', $userId)->first();

    $file = $request->file('face_data');

    $fileName = time() . '.' . $file->getClientOriginalExtension();

    Storage::disk('public')->putFileAs('face_data/', $file, $fileName);


    if ($faceData) {
      //Delete old file
      Storage::disk('public')->delete('face_data/' . $faceData->face_data_image);

      $faceData->update(['face_data' => '','face_data_image' => $fileName]);
    } else {
      FaceData::create([
        'user_id' => $userId,
        'face_data' => '',
        'face_data_image' => $fileName
      ]);
    }

    return redirect()->back()->with('success', 'Face data added successfully.');
  }

  public function removeFaceData($userId)
  {
    $faceData =  FaceData::where('user_id', $userId)->first();
    if($faceData) {
      Storage::disk('public')->delete('face_data/' . $faceData->face_data_image);
      $faceData->delete();
    }
    return redirect()->back()->with('success', 'Face data removed successfully.');
  }

}
