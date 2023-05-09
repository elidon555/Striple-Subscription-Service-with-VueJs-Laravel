<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateContentRequest;
use App\Http\Requests\SetSubscriptionPlanRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $perPage = request('per_page', 10);
        $search = request('search', '');
        $sortField = request('sort_field', 'updated_at');
        $sortDirection = request('sort_direction', 'desc');

        $query = SubscriptionPlan::query()
            ->where('name', 'like', "%$search%")
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);

        return SubscriptionPlanResource::collection($query);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(SetSubscriptionPlanRequest $request)
    {
        $data = $request->validated();
        $data['price_id'] = 121;
        $data['user_id'] = auth()->user()->id;

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET') );

        $response = $stripe->prices->create([
            'unit_amount' => $data['price']*10,
            'currency' => 'usd',
            'recurring' => ['interval' => strtolower(substr($data['interval'],0,-2))],
            'product' => 'prod_NqCbtic5sUZenP',
        ]);
        $data['price_id']=$response->id;

        $subscriptionPlan = SubscriptionPlan::create($data);


        return new SubscriptionPlanResource($subscriptionPlan);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(SetSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan)
    {
        $data = $request->validated();

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET') );

        $stripe->prices->update(
            $subscriptionPlan->price_id,
            ['currency_options' => ['unit_amount' => $data['price']*100]]
        );

        $subscriptionPlan->update($data);

        return new SubscriptionPlanResource($subscriptionPlan);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(SubscriptionPlan $SubscriptionPlan)
    {
        $SubscriptionPlan->delete();

        return response()->noContent();
    }
}
