<?php

namespace App\Support;

use App\Models\LiveSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * ربط Zoom عبر تطبيق Server-to-Server OAuth:
 * Zoom Marketplace ← Develop ← Build App ← Server-to-Server OAuth ← (Account ID, Client ID, Client Secret)
 * الصلاحيات المطلوبة: meeting:write:admin و meeting:read:admin (أو meeting:write:meeting / meeting:update:meeting / meeting:delete:meeting)
 */
class Zoom
{
    public static function configured(): bool
    {
        return (bool) (setting('zoom_account_id') && setting('zoom_client_id') && setting('zoom_client_secret'));
    }

    protected static function apiBase(): string
    {
        return rtrim((string) setting('zoom_api_base', 'https://api.zoom.us/v2'), '/');
    }

    protected static function oauthUrl(): string
    {
        return (string) setting('zoom_oauth_url', 'https://zoom.us/oauth/token');
    }

    protected static function token(): string
    {
        $cacheKey = 'zoom_token_'.md5(setting('zoom_account_id').setting('zoom_client_id'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::asForm()->withBasicAuth((string) setting('zoom_client_id'), (string) setting('zoom_client_secret'))
                ->timeout(20)->post(static::oauthUrl(), ['grant_type' => 'account_credentials', 'account_id' => setting('zoom_account_id')]);
            if (! $response->successful() || ! $response->json('access_token')) {
                throw new \RuntimeException('تعذّر الاتصال بحساب Zoom — تأكد من Account ID و Client ID و Client Secret وتفعيل التطبيق. ('.$response->status().')');
            }

            return (string) $response->json('access_token');
        });
    }

    protected static function request(string $method, string $path, array $body = [])
    {
        $response = Http::withToken(static::token())->acceptJson()->timeout(20)->{$method}(static::apiBase().$path, $body);
        if ($response->status() === 401) {
            Cache::forget('zoom_token_'.md5(setting('zoom_account_id').setting('zoom_client_id')));
        }
        if (! $response->successful()) {
            throw new \RuntimeException('خطأ من Zoom: '.($response->json('message') ?: $response->status()));
        }

        return $response;
    }

    protected static function payload(LiveSession $session): array
    {
        return [
            'topic' => mb_substr($session->title.' — '.$session->course?->title, 0, 190),
            'type' => 2,
            'start_time' => $session->starts_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'duration' => (int) $session->duration,
            'timezone' => config('app.timezone', 'Asia/Riyadh'),
            'agenda' => mb_substr((string) $session->description, 0, 1900),
            'settings' => [
                'join_before_host' => false,
                'waiting_room' => true,
                'mute_upon_entry' => true,
                'host_video' => true,
                'participant_video' => false,
            ],
        ];
    }

    /** إنشاء اجتماع أو تحديثه إن كان موجودًا */
    public static function sync(LiveSession $session): void
    {
        $user = (string) setting('zoom_user', 'me') ?: 'me';
        if ($session->zoom_meeting_id) {
            static::request('patch', '/meetings/'.$session->zoom_meeting_id, static::payload($session));

            return;
        }
        $meeting = static::request('post', '/users/'.rawurlencode($user).'/meetings', static::payload($session))->json();
        $session->forceFill([
            'zoom_meeting_id' => (string) ($meeting['id'] ?? ''),
            'join_url' => $meeting['join_url'] ?? null,
            'start_url' => $meeting['start_url'] ?? null,
            'password' => $meeting['password'] ?? null,
        ])->save();
    }

    public static function delete(LiveSession $session): void
    {
        if ($session->zoom_meeting_id) {
            try {
                static::request('delete', '/meetings/'.$session->zoom_meeting_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** رابط المضيف (start_url) ينتهي بعد ساعتين — نجلب رابطًا جديدًا عند الحاجة */
    public static function freshStartUrl(LiveSession $session): ?string
    {
        if (! $session->zoom_meeting_id || ! static::configured()) {
            return $session->start_url;
        }
        try {
            $url = static::request('get', '/meetings/'.$session->zoom_meeting_id)->json('start_url');
            if ($url) {
                $session->forceFill(['start_url' => $url])->save();
            }

            return $url ?: $session->start_url;
        } catch (\Throwable $e) {
            report($e);

            return $session->start_url;
        }
    }
}
