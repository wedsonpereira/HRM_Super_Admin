<?php

namespace App\Http\Controllers\tenant;

use App\Http\Controllers\Controller;
use App\Models\User;

class OrganisationHierarchyController extends Controller
{
  public function index()
  {
    $users = User::with(['reportingTo', 'designation'])->get();

    $hierarchy = $this->buildHierarchy($users);

    return view('tenant.organisation-hierarchy.index', compact('hierarchy'));
  }

  private function buildHierarchy($users, $parentId = null)
  {
    $result = [];
    foreach ($users as $user) {
      if ($user->reporting_to_id == $parentId) {
        $children = $this->buildHierarchy($users, $user->id);
        $result[] = [
          'id' => $user->id,
          'name' => $user->getFullName(),
          'code' => $user->code,
          'designation' => $user->designation->name ?? 'N/A',
          'profile_picture' => $user->getProfilePicture(),
          'initials' => $user->getInitials(),
          'children' => $children,
        ];
      }
    }
    return $result;
  }

}
