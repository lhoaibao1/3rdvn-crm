<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;use App\Http\Requests\StoreAffiliatePostbackRequest;use App\Support\Affiliate\UpsertAffiliateConversion;use Illuminate\Http\JsonResponse;
class AffiliatePostbackController extends Controller{public function __invoke(StoreAffiliatePostbackRequest $request,UpsertAffiliateConversion $action):JsonResponse{$conversion=$action->handle($request->validated(),'hyperlead');return response()->json(['ok'=>true,'id'=>$conversion->getKey(),'conversion_id'=>$conversion->conversion_id]);}}
