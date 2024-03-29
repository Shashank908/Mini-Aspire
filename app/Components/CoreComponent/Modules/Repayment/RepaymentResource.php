<?php

namespace App\Components\CoreComponent\Modules\Repayment;

use App\Components\CoreComponent\Modules\Client\ClientResource;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 *  
 */
class RepaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $repayment = $this->resource;
        $array = $repayment->toArray();
        $array['client_id'] = $repayment->loan->client->id;
        return $array;
    }
}
