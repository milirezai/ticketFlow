<?php

namespace App\Services\Otp;

use App\Models\Service\Otp\Otp;
use App\Models\User\User;
use App\Notifications\Service\Otp\SendOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
	protected int $ttlminutes = 5;

	public function setTtl(int $minutes): self
	{
		$this->ttlminutes = $minutes;
		return $this;
	}

	public function getTtl(): int
	{
		return $this->ttlminutes;
	}

	public function generateAndSend(User $user): void
	{
		$userUnusedOtp = $user->unusedOtp();
		if($userUnusedOtp){
			Otp::find($userUnusedOtp->id)->update(["is_used"=>true]);
		}
		$code = Str::random(5);
		Otp::create([
			"user_id" => $user->id,
			"code" => Hash::make($code),
			"expired_at" => now()->addMinutes($this->ttlminutes)
		]);
		$user->notify(new SendOtpNotification($code));
	}

	public function verify(User $user, string $code): bool
	{
		$otpRecord = Otp::find($user->unusedOtp()->id);
		if (!$otpRecord || !Hash::check($code, $otpRecord->code)){
			return false;
		}
		$otpRecord->update(["is_used" => true]);
		return true;
	}
}
