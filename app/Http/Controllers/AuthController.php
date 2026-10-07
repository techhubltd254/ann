<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function form(){return view('auth.login');}
 public function login(Request $r){$v=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($v,$r->boolean('remember')))return back()->withErrors(['email'=>'These credentials could not be verified.'])->onlyInput('email');$r->session()->regenerate();return redirect()->intended($r->user()->is_admin?route('admin.index'):route('home'));}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('home');}
}
