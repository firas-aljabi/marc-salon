<?php

namespace App\Http\Requests\Rservation;

use App\Statuses\AmountType;
use App\Statuses\PaymentStatus;
use App\Statuses\PaymentWay;
use App\Statuses\ReasonCancleDelay;
use App\Statuses\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'reservation_id' => 'required|exists:reservations,id',
            'payment_status' => ['required', Rule::in(PaymentStatus::$statuses)],
            'payment_way' => ['required', Rule::in(PaymentWay::$statuses)],
            'status' => ['required', Rule::in(ReservationStatus::$statuses)],
            'delay_date' => 'nullable',
            'reason_cancle_delay' => ['nullable', 'required_if:status,2,3', Rule::in(ReasonCancleDelay::$statuses)],
            'reservation_amount' => 'required',
            'notes' => 'nullable',
            'attachment' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
