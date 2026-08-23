import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { cn } from '../lib/utils';

interface QuickActionProps {
  /** Emoji or short glyph shown in the icon well */
  icon: string;
  label: string;
  href: string;
  variant?: 'default' | 'primary';
}

export default function QuickAction({ icon, label, href, variant = 'default' }: QuickActionProps) {
  const navigate = useNavigate();

  return (
    <motion.button
      whileHover={{ scale: 1.03, y: -1 }}
      whileTap={{ scale: 0.97 }}
      onClick={() => navigate(href)}
      className={cn(
        'flex items-center gap-2.5 px-4 py-2.5 rounded-full text-sm font-semibold transition-all duration-200 border',
        variant === 'primary'
          ? 'bg-gradient-to-r from-violet to-indigo text-white border-transparent shadow-lg shadow-violet/30 hover:shadow-xl hover:shadow-violet/50 hover:brightness-110'
          : 'bg-surface border-border text-text-secondary hover:text-white hover:border-border-glow hover:bg-surface-hover hover:shadow-lg hover:shadow-violet/20'
      )}
    >
      <span
        className={cn(
          'w-7 h-7 rounded-full flex items-center justify-center text-sm transition-shadow',
          variant === 'primary' ? 'bg-white/15' : 'bg-surface-light'
        )}
      >
        {icon}
      </span>
      <span>{label}</span>
    </motion.button>
  );
}
