<?php

namespace App\Models\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class LoginHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'successful' => 'boolean',
        'is_new_device' => 'boolean',
        'logged_in_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse the request into a normalized context array.
     */
    protected static function context(Request $request): array
    {
        $agent = new Agent;
        $agent->setUserAgent($request->userAgent());

        $ip = static::clientIp($request);

        return [
            'ip_address' => $ip,
            'country' => optional(geoip($ip))->iso_code,   // null if geoip not installed
            'city' => optional(geoip($ip))->city,
            'device_type' => $agent->deviceType(),             // "desktop" / "phone" / "tablet"
            'platform' => $agent->platform(),               // "Windows", "Android", ...
            'browser' => $agent->browser(),                // "Chrome", "Safari", ...
            'user_agent' => $request->userAgent(),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'fingerprint_hash' => hash('sha256', implode('|', [
                $ip,
                $request->userAgent(),
                $agent->platform(),
            ])),
        ];
    }

    protected static function clientIp(Request $request): string
    {
        if ($cf = $request->header('CF-Connecting-IP')) {
            return $cf;
        }

        if ($xff = $request->header('X-Forwarded-For')) {
            return trim(explode(',', $xff)[0]); // first IP in the chain
        }

        return $request->ip() ?? '0.0.0.0';
    }

    /**
     * Record a login attempt for a known user (success or failure).
     */
    public static function record(User $user, Request $request, bool $successful, ?string $reason = null): self
    {
        $ctx = static::context($request);

        $isNew = $successful && ! static::where('user_id', $user->id)
            ->where('fingerprint_hash', $ctx['fingerprint_hash'])
            ->where('successful', true)
            ->exists();

        return static::create(array_merge($ctx, [
            'user_id' => $user->id,
            'successful' => $successful,
            'failure_reason' => $reason,
            'is_new_device' => $isNew,
            'logged_in_at' => Carbon::now(),
        ]));
    }

    /**
     * Record an attempt where no matching user exists.
     */
    public static function recordFailed(string $username, Request $request, string $reason): self
    {
        return static::create(array_merge(static::context($request), [
            'user_id' => null,
            'username_attempted' => $username,
            'successful' => false,
            'failure_reason' => $reason,
            'logged_in_at' => Carbon::now(),
        ]));
    }
}
