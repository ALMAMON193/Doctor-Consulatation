<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @method static create(mixed $validated, \Illuminate\Contracts\Auth\Authenticatable $user)
 */
class User extends Authenticatable
{

    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'otp',
        'otp_created_at',
        'is_otp_verified',
        'otp_expires_at',
        'reset_password_token',
        'reset_password_token_expire_at',
        'delete_token',
        'delete_token_expires_at',
        'avatar',
        'user_type',
        'is_verified',
        'verified_at',
        'terms_and_conditions',
        'avatar'
    ];
    protected $hidden = [
        'password',
        'otp',
        'otp_created_at',
        'is_otp_verified',
        'otp_expires_at',
        'reset_password_token',
        'reset_password_token_expire_at',
        'delete_token',
        'delete_token_expires_at',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function personalDetails(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPersonalDetail::class);
    }

    public function address(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserAddress::class);
    }

    public function doctorProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DoctorProfile::class, 'user_id', 'id');
    }
    public function patient(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Patient::class);
    }
    public function patientMember(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PatientMember::class);
    }
    public function personalDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPersonalDetail::class);
    }
    //check user complete the profile
    public function hasCompletedProfile(): bool
    {
        // Check if personal details are filled
        $personal = $this->personalDetails;
        if (!$personal || !$personal->date_of_birth || !$personal->cpf || !$personal->gender || !$personal->account_type) {
            return false;
        }
        // Check if doctor profile exists and important fields are filled
        $doctor = $this->doctorProfile;
        if (!$doctor || !$doctor->company_name || !$doctor->monthly_income || !$doctor->company_phone || !$doctor->company_income) {
            return false;
        }
        return true;
    }
}
