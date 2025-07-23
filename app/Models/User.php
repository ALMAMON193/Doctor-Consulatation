<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Doctor;
use App\Models\UserAddress;
use App\Models\DoctorProfile;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserPersonalDetail;
use App\Models\UserFinancialDetail;
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



}
