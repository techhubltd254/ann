import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Mail, Lock, Eye, EyeOff, LogIn, ShieldCheck } from 'lucide-react';
import { cn } from '../lib/utils';

const inputCls =
  'w-full bg-slate-dark/80 border border-border rounded-lg pl-10 pr-3 py-2.5 text-sm text-white placeholder:text-text-muted outline-none focus:border-violet/60 focus:ring-2 focus:ring-violet/15 transition-all';

export default function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('admin@muranga.go.ke');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [remember, setRemember] = useState(true);
  const [loading, setLoading] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setTimeout(() => navigate('/overview'), 900);
  };

  return (
    <div className="min-h-screen bg-deep flex items-center justify-center p-4 relative overflow-hidden">
      {/* Ambient glow background */}
      <div className="absolute inset-0 pointer-events-none">
        <div className="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-violet/15 blur-[120px]" />
        <div className="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-emerald/10 blur-[120px]" />
        <div className="absolute top-1/3 right-1/4 w-64 h-64 rounded-full bg-cyan/10 blur-[100px]" />
      </div>

      <motion.div
        initial={{ opacity: 0, y: 24, scale: 0.97 }}
        animate={{ opacity: 1, y: 0, scale: 1 }}
        transition={{ duration: 0.45, ease: 'easeOut' }}
        className="w-full max-w-md relative"
      >
        <div className="glass-card !bg-slate-dark/90 backdrop-blur-xl p-8 shadow-2xl shadow-black/50">
          {/* Logo */}
          <div className="flex flex-col items-center mb-8">
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ type: 'spring', damping: 12, delay: 0.15 }}
              className="w-14 h-14 rounded-2xl bg-gradient-to-br from-violet to-indigo flex items-center justify-center text-white font-black text-2xl shadow-xl shadow-violet/30 mb-4"
            >
              M
            </motion.div>
            <h1 className="text-xl font-black text-white">Murang'a County Admin</h1>
            <p className="text-xs text-text-muted uppercase tracking-widest font-semibold mt-1">KICC Platform</p>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Email Address</label>
              <div className="relative">
                <Mail size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@muranga.go.ke"
                  className={inputCls}
                />
              </div>
            </div>

            <div>
              <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Password</label>
              <div className="relative">
                <Lock size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
                <input
                  type={showPassword ? 'text' : 'password'}
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••••"
                  className={cn(inputCls, 'pr-10')}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((s) => !s)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-white transition-colors"
                >
                  {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between text-xs">
              <label className="flex items-center gap-2 text-text-secondary cursor-pointer">
                <input
                  type="checkbox"
                  checked={remember}
                  onChange={(e) => setRemember(e.target.checked)}
                  className="w-3.5 h-3.5 rounded border-border bg-surface accent-violet"
                />
                Remember me
              </label>
              <a href="#" className="text-violet hover:text-indigo font-semibold transition-colors">
                Forgot password?
              </a>
            </div>

            <button
              type="submit"
              disabled={loading}
              className={cn(
                'btn-primary w-full !py-2.5 flex items-center justify-center gap-2',
                loading && 'opacity-80 cursor-wait'
              )}
            >
              {loading ? (
                <>
                  <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  Authenticating...
                </>
              ) : (
                <>
                  <LogIn size={15} /> Sign In to Dashboard
                </>
              )}
            </button>
          </form>

          {/* Footer */}
          <div className="mt-6 pt-5 border-t border-border flex items-center justify-center gap-2 text-[11px] text-text-muted">
            <ShieldCheck size={12} className="text-emerald" />
            Secured with county SSO · Kenya Data Protection Act compliant
          </div>
        </div>

        <p className="text-center text-[11px] text-text-muted mt-5">
          Demo: any credentials work · access is audited per KICC policy
        </p>
      </motion.div>
    </div>
  );
}
