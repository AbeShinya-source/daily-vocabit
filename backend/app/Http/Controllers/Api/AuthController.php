<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 認証コードを送信（登録の第一段階）
     */
    public function sendVerificationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 既存ユーザーチェック
        if (User::where('email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['このメールアドレスは既に登録されています'],
            ]);
        }

        // 6桁の認証コードを生成
        $code = EmailVerificationCode::generateCode();

        // 既存の認証コードを削除して新規作成
        EmailVerificationCode::where('email', $validated['email'])->delete();

        EmailVerificationCode::create([
            'email' => $validated['email'],
            'code' => $code,
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'expires_at' => now()->addMinutes(10),
        ]);

        // メール送信
        Mail::to($validated['email'])->send(new VerificationCodeMail($code, $validated['name']));

        return response()->json([
            'success' => true,
            'message' => '認証コードをメールで送信しました',
        ]);
    }

    /**
     * 認証コードを検証して登録完了
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|string|size:6',
        ]);

        $verification = EmailVerificationCode::where('email', $validated['email'])
            ->where('code', $validated['code'])
            ->first();

        if (!$verification) {
            throw ValidationException::withMessages([
                'code' => ['認証コードが正しくありません'],
            ]);
        }

        if (!$verification->isValid()) {
            $verification->delete();
            throw ValidationException::withMessages([
                'code' => ['認証コードの有効期限が切れています。再度登録してください'],
            ]);
        }

        // ユーザーを作成
        $user = User::create([
            'name' => $verification->name,
            'email' => $verification->email,
            'password' => $verification->password, // 既にハッシュ化済み
            'email_verified_at' => now(),
        ]);

        // 認証コードを削除
        $verification->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ユーザー登録が完了しました',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
            ]
        ], 201);
    }

    /**
     * 認証コードを再送信
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
        ]);

        $verification = EmailVerificationCode::where('email', $validated['email'])->first();

        if (!$verification) {
            throw ValidationException::withMessages([
                'email' => ['登録情報が見つかりません。最初から登録をやり直してください'],
            ]);
        }

        // 新しいコードを生成
        $code = EmailVerificationCode::generateCode();
        $verification->update([
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        // メール送信
        Mail::to($validated['email'])->send(new VerificationCodeMail($code, $verification->name));

        return response()->json([
            'success' => true,
            'message' => '認証コードを再送信しました',
        ]);
    }

    /**
     * ユーザー登録（従来の方式、後方互換性のため残す）
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ユーザー登録が完了しました',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
            ]
        ], 201);
    }

    /**
     * ログイン
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['メールアドレスまたはパスワードが正しくありません'],
            ]);
        }

        // 既存のトークンを削除（オプション：単一セッションを維持する場合）
        // $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ログインしました',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
            ]
        ]);
    }

    /**
     * ログアウト
     */
    public function logout(Request $request): JsonResponse
    {
        // 現在のトークンを削除
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました',
        ]);
    }

    /**
     * 現在のユーザー情報を取得
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ]
        ]);
    }

    /**
     * Google OAuth コールバック
     */
    public function googleCallback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'google_id' => 'required|string',
            'email' => 'required|string|email',
            'name' => 'required|string',
            'avatar' => 'nullable|string',
        ]);

        // 既存のGoogleユーザーを検索、または新規作成
        $user = User::where('google_id', $validated['google_id'])
            ->orWhere('email', $validated['email'])
            ->first();

        if ($user) {
            // 既存ユーザー：Google情報を更新
            $user->update([
                'google_id' => $validated['google_id'],
                'name' => $validated['name'],
                'avatar' => $validated['avatar'],
            ]);
        } else {
            // 新規ユーザー作成
            $user = User::create([
                'google_id' => $validated['google_id'],
                'email' => $validated['email'],
                'name' => $validated['name'],
                'avatar' => $validated['avatar'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Googleでログインしました',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
            ]
        ]);
    }
}
