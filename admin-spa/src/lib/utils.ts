export function cn(...classes: (string | undefined | false)[]): string {
  return classes.filter(Boolean).join(' ');
}

export function formatKES(amount: number): string {
  return new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

export function timeAgo(date: Date): string {
  const seconds = Math.floor((new Date().getTime() - date.getTime()) / 1000);
  if (seconds < 60) return 'just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

export function getStatusColor(status: string): string {
  switch (status) {
    case 'active': return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30';
    case 'featured': return 'bg-violet-500/15 text-violet-400 border border-violet-500/30';
    case 'review': return 'bg-amber-500/15 text-amber-400 border border-amber-500/30';
    case 'pending': return 'bg-rose-500/15 text-rose-400 border border-rose-500/30';
    default: return 'bg-gray-500/15 text-gray-400 border border-gray-500/30';
  }
}