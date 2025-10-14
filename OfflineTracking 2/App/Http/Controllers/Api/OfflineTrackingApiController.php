<?php

namespace Modules\OfflineTracking\App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\DeviceStatusLog;
use App\Models\UserDevice;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OfflineTrackingApiController extends Controller
{
  public function syncTrackingFromMobile(Request $request)
  {
    $latitude = $request->latitude;
    $longitude = $request->longitude;

    $createdAt = $request->createdAt;

    if ($latitude == null || $longitude == null) {
      return Error::response('Latitude and longitude are required');
    }

    if ($createdAt == null || $createdAt == '') {
      return Error::response('Created at is required');
    }

    $attendance = Attendance::where('user_id', auth()->user()->id)
      ->whereDate('created_at', Carbon::now())
      ->first();

    if ($attendance == null) {
      return Error::response('No attendance found for today');
    }

    $dateFormat = 'd-m-Y';

    $date = Carbon::parse($createdAt)->format($dateFormat);

    $tracking = new Activity();
    $tracking->attendance_id = $attendance->id;
    $tracking->latitude = $latitude;
    $tracking->longitude = $longitude;
    $tracking->activity = $request->activity;
    $tracking->accuracy = $request->accuracy;
    $tracking->battery_percentage = $request->batteryPercentage;
    $tracking->is_gps_on = $request->isGPSOn;
    $tracking->is_wifi_on = $request->isWifiOn;
    $tracking->is_mock = $request->isMock;
    $tracking->type = $request->type == 'still' ? 'still' : 'travelling';
    $tracking->signal_strength = $request->signalStrength;
    $tracking->created_at = $date;
    $tracking->is_offline = true;
    $tracking->altitude = $request->altitude;
    $tracking->speed = $request->speed;
    $tracking->bearing = $request->bearing;
    $tracking->save();


    return Success::response('Tracking synced successfully');
  }


  public function bulkUpdateDeviceStatus(Request $request)
  {
    $deviceLogs = $request->items; // Expecting an array of device logs

    Log::info('Request Received', ['items' => $deviceLogs]);

    if (!is_array($deviceLogs) || count($deviceLogs) === 0) {
      return Error::response('Invalid or empty data provided.');
    }

    $decodedDeviceLogs = [];
    foreach ($deviceLogs as $log) {
      // Decode each JSON string
      $decodedLog = json_decode($log, true);

      if ($decodedLog === null) {
        Log::error('Failed to decode log:', ['log' => $log]);
        return Error::response('Invalid data format.');
      }

      $decodedDeviceLogs[] = $decodedLog;
    }

    $device = UserDevice::where('user_id', auth()->id())->first();

    if (!$device) {
      return Error::response('Device not found for the authenticated user.');
    }

    $bulkInsertData = [];
    foreach ($decodedDeviceLogs as $log) {
      try {
        $createdAtDateTime = Carbon::createFromFormat('d-m-Y H:i:s', $log['createdAt'])
          ->format('Y-m-d H:i:s');

        $bulkInsertData[] = [
          'uid' => $log['uid'] ?? null,
          'device_id' => $device->id,
          'user_id' => $device->user_id,
          'device_type' => $device->device_type,
          'brand' => $device->brand,
          'board' => $device->board,
          'sdk_version' => $device->sdk_version,
          'model' => $device->model,
          'token' => $device->token,
          'app_version' => $device->app_version,
          'battery_percentage' => $log['batteryPercentage'] ?? null,
          'is_gps_on' => $log['isGPSOn'] ?? false,
          'is_wifi_on' => $log['isWifiOn'] ?? false,
          'is_mock' => $log['isMock'] ?? false,
          'signal_strength' => $log['signalStrength'] ?? null,
          'address' => $log['address'] ?? null,
          'is_charging' => $log['isCharging'] ?? false,
          'latitude' => $log['latitude'] ?? null,
          'longitude' => $log['longitude'] ?? null,
          'bearing' => $log['bearing'] ?? null,
          'horizontalAccuracy' => $log['horizontalAccuracy'] ?? null,
          'altitude' => $log['altitude'] ?? null,
          'verticalAccuracy' => $log['verticalAccuracy'] ?? null,
          'course' => $log['course'] ?? null,
          'courseAccuracy' => $log['courseAccuracy'] ?? null,
          'speed' => $log['speed'] ?? null,
          'speedAccuracy' => $log['speedAccuracy'] ?? null,
          'created_by_id' => auth()->id(),
          'created_at' => $createdAtDateTime,
          'is_offline_record' => true,
          'offline_created_at' => now(),
        ];
      } catch (Exception $e) {
        Log::error('Failed to process device log: ' . json_encode($log) . '. Error: ' . $e->getMessage());
        continue; // Skip invalid log
      }
    }

    if (count($bulkInsertData) > 0) {
      DeviceStatusLog::insert($bulkInsertData);
      return Success::response('Device statuses updated successfully.');
    }

    return Error::response('No valid data to process.');
  }

  public function bulkActivityStatusUpdate(Request $request)
  {
    $statusUpdates = $request->items; // Expecting an array of status updates

    if (!is_array($statusUpdates) || count($statusUpdates) === 0) {
      return Error::response('Invalid or empty data provided.');
    }

    $decodedStatusUpdates = [];
    foreach ($statusUpdates as $log) {
      // Decode each JSON string
      $decodedLog = json_decode($log, true);

      if ($decodedLog === null) {
        Log::error('Failed to decode log:', ['log' => $log]);
        return Error::response('Invalid data format.');
      }

      $decodedStatusUpdates[] = $decodedLog;
    }

    $bulkInsertData = [];

    foreach ($decodedStatusUpdates as $update) {

      if (!isset($update['status'], $update['latitude'], $update['longitude'])) {
        Log::error('Invalid status update: ' . json_encode($update));
        continue;
      }

      $createdAtDateTime = Carbon::createFromFormat('d-m-Y H:i:s', $update['createdAt'])
        ->format('Y-m-d H:i:s');

      $bulkInsertData[] = [
        'uid' => $update['uid'] ?? null,
        'is_mock' => $update['isMock'] ?? false,
        'battery_percentage' => $update['batteryPercentage'] ?? null,
        'is_gps_on' => $update['isGPSOn'] ?? false,
        'is_wifi_on' => $update['isWifiOn'] ?? false,
        'signal_strength' => $update['signalStrength'] ?? null,
        'type' => $update['status'] == 'still' ? 'still' : 'travelling',
        'activity' => $update['activity'] ?? null,
        'accuracy' => $update['accuracy'] ?? null,
        'latitude' => $update['latitude'],
        'longitude' => $update['longitude'],
        'bearing' => $update['bearing'] ?? null,
        'horizontalAccuracy' => $update['horizontalAccuracy'] ?? null,
        'altitude' => $update['altitude'] ?? null,
        'verticalAccuracy' => $update['verticalAccuracy'] ?? null,
        'course' => $update['course'] ?? null,
        'courseAccuracy' => $update['courseAccuracy'] ?? null,
        'speed' => $update['speed'] ?? null,
        'speedAccuracy' => $update['speedAccuracy'] ?? null,
        'created_by_id' => auth()->id(),
        'created_at' => $createdAtDateTime,
        'is_offline_record' => true,
        'offline_created_at' => now(),
      ];
    }

    if (count($bulkInsertData) > 0) {
      Activity::insert($bulkInsertData);
      return Success::response('Statuses updated successfully.');
    }

    return Error::response('No valid data to process.');
  }
}
