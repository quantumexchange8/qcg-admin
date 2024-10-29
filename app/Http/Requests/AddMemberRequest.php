<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class AddMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:' . User::class],
            'dial_code' => ['required'],
            'phone' => ['required', 'regex:/^[0-9]+$/', 'unique:' . User::class],
            'password' => ['required', Password::min(8)->letters()->symbols()->numbers()->mixedCase(), 'confirmed'],
            'password_confirmation' => ['required','same:password'],
            'upline' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'name' => trans('public.full_name'),
            'email' => trans('public.email'),
            'dial_code' => trans('public.phone_code'),
            'phone' => trans('public.phone_number'),
            'password' => trans('public.password'),
            'password_confirmation' => trans('public.confirm_password'),
            'upline' => trans('public.upline'),
        ];
    }
}
