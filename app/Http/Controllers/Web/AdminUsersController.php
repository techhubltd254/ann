<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** Role assignment across the four administration tiers. */
class AdminUsersController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()?->hasRole('kicc_admin'), 403, 'Only a KICC administrator can manage roles.');
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $q = trim((string) $request->get('q'));
        $users = User::with('roles')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('experience.admin.users', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
            'roleCounts' => Role::withCount('users')->pluck('users_count', 'name'),
            'permissions' => Permission::orderBy('name')->pluck('name'),
            'q' => $q,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->guard($request);
        $d = $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);
        if ($user->hasRole('kicc_admin') && !in_array('kicc_admin', $d['roles'] ?? [], true)) {
            abort_unless(User::role('kicc_admin')->where('id', '!=', $user->id)->where('status', 'active')->exists(), 422, 'The last active KICC administrator cannot be removed.');
        }
        $before = $user->getRoleNames()->all();
        $user->syncRoles($d['roles'] ?? []);
        $after = $user->getRoleNames()->all();

        return back()->with('status', sprintf(
            '%s roles: [%s] → [%s]',
            $user->email,
            implode(', ', $before) ?: 'none',
            implode(', ', $after) ?: 'none'
        ));
    }
}
