<?php

namespace App\Services\Otp;

use App\Models\Service\Otp\Otp;
use App\Models\User\User;
use Illuminate\Support\Facades\Hash;

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

	public function generate(User $user): int
	{
		$userUnusedOtp = $user->unusedOtp();
		if ($userUnusedOtp) {
			Otp::find($userUnusedOtp->id)->update(["is_used" => true]);
		}
		$code = random_int(100000, 999999);
		Otp::create([
			"user_id" => $user->id,
			"code" => Hash::make($code),
			"expired_at" => now()->addMinutes($this->ttlminutes)
		]);
		return $code;
	}

	public function verify(User $user, string $code): array
	{
		$otpRecord = Otp::find($user->unusedOtp()->id);
		if (!$otpRecord) {
			return ["success" => false, "message" => "No OTP found for this user."];
		} else if (!Hash::check($code, $otpRecord->code)) {
			return ["success" => false, "message" => "Invalid OTP code."];
		} else if ($otpRecord->expired_at->isPast()) {
			return ["success" => false, "message" => "OTP has expired."];
		}
		$otpRecord->update(["is_used" => true]);
		return ["success" => true, "message" => "OTP verified successfully."];
	}
}
