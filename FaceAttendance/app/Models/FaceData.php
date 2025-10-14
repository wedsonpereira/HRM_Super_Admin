<?php

namespace Modules\FaceAttendance\app\Models;

use App\Models\User;
use App\Traits\TenantTrait;
use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class FaceData extends Model implements AuditableContract
{
  use Auditable, UserActionsTrait, TenantTrait, SoftDeletes;

  protected $table = 'face_data';

  protected $fillable = [
    'user_id',
    'face_data',
    'face_data_image',
    'created_by_id',
    'updated_by_id',
    'tenant_id',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
