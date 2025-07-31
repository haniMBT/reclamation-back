<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'Matricule' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): bool
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('Matricule', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'Matricule' => __('auth.failed'),
            ]);
        }
        $hasExistingProfil = DB::table('Busers')
            ->where('Matricule',Auth::user()->Matricule)
            ->whereRaw('privilege IN (SELECT code FROM p_profils)')
            ->get()->isNotEmpty();

        if (! $hasExistingProfil) {
            $this->destroy();
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'Privilege' => "Vous n'avez pas les privilèges pour accéder à cette plateforme",
            ]);
            return false;
        }

        RateLimiter::clear($this->throttleKey());
        return true;

//        $dr_id = Auth::user()->Nom_DR;
//        $str_id = Auth::user()->Structure;
//        $str_name = DB::table('Bagences')->where('code_ag',$str_id)
//            ->select('nom_ag')
//            ->pluck('nom_ag');
//
//        if(Auth::user()->privilege != "Admin")
//        {
//            Session::put('dr_id',$dr_id);
//            Session::put('str_id',$str_id);
//            Session::put('str_name',$str_name);
//        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'Matricule' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('Matricule')).'|'.$this->ip());
    }

    public function destroy()
    {
        Auth::guard('api')->logout();
    }
}
