<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use QrCode;
use App\Models\Admin;

class TwoFactorAuthController extends Controller
{
    public function show2faForm(Request $request)
    {
        // Check if the user already has a secret key
        $user = Admin::find(session('admin_id'));
        if ($user->google2fa_secret) {
            // Redirect to OTP verification if the secret key exists
            return redirect()->route('2fa.verifyForm');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey(32);

        $text = sprintf('otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode('My Bank Passbook'),
            rawurlencode('admin@gmail.com'),
            $secret,
            rawurlencode('My Bank Passbook')
        );


        $QR_Image = QrCode::size(200)->generate($text);

        // $QR_Image = new QrCode($text);
        // $QR_Image->setSize(200);
        // $QR_Image->setMargin(10);
        // $QR_Image->setEncoding('UTF-8');
        // $QR_Image->setWriterByName('png');
        // $QR_Image->setValidateResult(false);
        // $QR_Image = base64_encode($QR_Image->writeString());

        return view('2fa.setup', compact('QR_Image', 'secret'));
    }

    public function setup2fa(Request $request)
    {
        $request->validate([
            'secret' => 'required',
            'verify_code' => 'required|digits:6',
        ]);

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($request->input('secret'), $request->input('verify_code'));
        if ($valid) {
            $user = Admin::find(session('admin_id'));
            $user->google2fa_secret = $request->secret;
            $user->save();
            session(['2fa_verified' => true]);

            return redirect()->intended('dashboard');
        } else {
            return back()->with('error', 'The provided OTP is invalid,Plese Scan again!');
        }
    }

    public function showVerifyForm()
    {
        return view('2fa.verify');
    }

    public function verify2fa(Request $request)
    {
        // dd($request->all());
        $user = Admin::find(session('admin_id'));
        $request->validate([
            'verify_code' => 'required|digits:6',
        ]);

        $google2fa = new Google2FA();
        $user = Admin::find(session('admin_id'));

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->input('verify_code'));
        if ($valid) {
            session(['2fa_verified' => true]);
            return redirect()->intended('dashboard');
        } else {
            return back()->with('error','The provided OTP is invalid.');
        }
    }

}

?>
