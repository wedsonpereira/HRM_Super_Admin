<?php

namespace App\Http\Controllers\SuperAdmin;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Enums\DomainRequestStatus;
use App\Enums\PlanDurationType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin\DomainRequest;
use App\Models\SuperAdmin\OfflineRequest;
use App\Models\SuperAdmin\Order;
use App\Models\SuperAdmin\Plan;
use App\Models\SuperAdmin\SaSettings as Settings;
use App\Services\AddonService\IAddonService;
use App\Services\PlanService\ISubscriptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

class CustomerController extends Controller
{
  private ISubscriptionService $subscriptionService;

  private IAddonService $addonService;

  function __construct(ISubscriptionService $subscriptionService, IAddonService $addonService)
  {
    $this->subscriptionService = $subscriptionService;
    $this->addonService = $addonService;
  }

  public function getAddUserTotalAmountAjax(Request $request)
  {
    $usersCount = $request->users;

    return Success::response($this->subscriptionService->getAddUserTotalAmount($usersCount));
  }

  public function index()
  {
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
      return redirect()->route('superAdmin.dashboard');
    }

    // Fetch active plan details
    $activePlan = null;
    if ($user->hasActivePlan()) {
      $activePlan = Plan::find($user->plan_id);
    }

    $availablePlans = Plan::where('status', Status::ACTIVE)->get();

    $orders = Order::where('user_id', $user->id)
      ->get();


    $offlineRequests = OfflineRequest::where('user_id', $user->id)
      ->get();

    $pageConfigs = ['myLayout' => 'front'];

    $settings = Settings::first();

    $domainRequest = DomainRequest::where('user_id', $user->id)
      ->orderBy('created_at', 'desc')
      ->first();

    $domainRequests = DomainRequest::where('user_id', $user->id)
      ->orderBy('created_at', 'desc')
      ->get();

    return view('superAdmin.customer.index', [
      'pageConfigs' => $pageConfigs,
      'orders' => $orders,
      'activePlan' => $activePlan,
      'subscription' => $user->activeSubscription(),
      'planExpiryDate' => $user->plan_expired_date,
      'domainRequest' => $domainRequest,
      'domainRequests' => $domainRequests,
      'availablePlans' => $availablePlans,
      'offlineRequests' => $offlineRequests,
      'notificationSettings' => [
        'email_notifications' => true,
        'sms_notifications' => true,
        'push_notifications' => true,
      ],
      'gateways' => [
        'paypal' => $settings->paypal_enabled,
        'razorpay' => $settings->razorpay_enabled,
        'offline' => $settings->offline_payment_enabled,
        'stripe' => $this->addonService->isStripeEnabled(),
        'offlineInstructions' => $settings->offline_payment_instructions,
      ],
    ]);
  }

  public function getOrderDetailsAjax($id)
  {
    $user = auth()->user();
    $order = Order::where('user_id', $user->id)
      ->with('plan')
      ->where('id', $id)
      ->first();

    if (!$order) {
      return Error::response('Order not found');
    }

    return Success::response($order);
  }

  public function downloadInvoice($id)
  {
    $user = auth()->user();
    $order = Order::where('user_id', $user->id)
      ->with(['plan', 'user'])
      ->where('id', $id)
      ->first();

    if (!$order) {
      return redirect()->back()->with('error', 'Order not found');
    }

    $settings = Settings::first();
    $pdf = PDF::loadView('superAdmin.customer.invoice-pdf', compact('order', 'settings'));
    
    return $pdf->download('invoice-' . $order->id . '.pdf');
  }


  public function requestDomain(Request $request)
  {
    $validated = $request->validate([
      'domain' => 'required|min:5',
    ]);

    //Regex to check a text has no special characters or spaces or tabs
    if (!preg_match('/^[a-zA-Z0-9]+$/', $validated['domain'])) {
      return redirect()->back()->with('error', 'Domain name should not contain special characters or spaces');
    }

    if (Domain::where('domain', $validated['domain'])->exists()) {
      return redirect()->back()->with('error', 'This domain is already taken');
    }

    // Check if there's already a pending or approved request for this domain
    $existingRequest = DomainRequest::where('name', $validated['domain'])
      ->whereIn('status', [DomainRequestStatus::PENDING, DomainRequestStatus::APPROVED])
      ->exists();
      
    if ($existingRequest) {
      return redirect()->back()->with('error', 'There is already a pending or approved request for this domain');
    }

    // Check if the user already has a pending domain request
    $user = auth()->user();
    $userPendingRequest = DomainRequest::where('user_id', $user->id)
      ->where('status', DomainRequestStatus::PENDING)
      ->exists();

    if ($userPendingRequest) {
      return redirect()->back()->with('error', 'You already have a pending domain request. Please wait for approval or cancel the existing request.');
    }

    $domainRequest = new DomainRequest();
    $domainRequest->user_id = $user->id;
    $domainRequest->name = $validated['domain'];
    $domainRequest->created_by_id = $user->id;
    $domainRequest->status = DomainRequestStatus::PENDING;
    $domainRequest->save();

    return redirect()->back()->with('success', 'Domain request submitted successfully');
  }


  public function getBalancePriceForUpgradeAjax(Request $request)
  {
    return Success::response($this->subscriptionService->getDifferencePriceForUpgrade($request->planId));
  }

  public function getPriceForRenewalAjax()
  {
    return Success::response($this->subscriptionService->getRenewalAmount());
  }


  public function getSubscriptionInfoAjax()
  {
    return Success::response($this->subscriptionService->getSubscription());
  }

  public function cancelDomainRequest($id)
  {
    $domainRequest = DomainRequest::find($id);

    if (!$domainRequest) {
      return redirect()->back()->with('error', 'Domain request not found');
    }

    $domainRequest->status = DomainRequestStatus::CANCELLED;
    $domainRequest->save();

    return redirect()->back()->with('success', 'Domain request cancelled successfully');
  }


  private function getPricePerDayBasedOnPlanDurationType(Plan $plan)
  {
    return match ($plan->duration_type) {
      PlanDurationType::DAYS => $plan->price,
      PlanDurationType::MONTHS => $plan->price / 30,
      PlanDurationType::YEARS => $plan->price / 365,
      default => 0,
    };

  }
}
