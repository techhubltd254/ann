import { useMemo, useState } from 'react';
import { Eye, Edit3, Trash2 } from 'lucide-react';
import { cn, formatKES, getStatusColor } from '../lib/utils';

// Keys that get their own dedicated rendering (thumbnail, badge, price…)
const RESERVED_KEYS = new Set([
  'id', 'name', 'emoji', 'status', 'price', 'gradient', 'subtitle', 'description',
  'features', 'permissions', 'title', 'duration', 'resolution', 'engine', 'frames',
  'size', 'views', 'author', 'lead', 'budget', 'members', 'detail', 'ip', 'level',
  'time', 'actor', 'target', 'action', 'schedule', 'format', 'lastRun', 'by',
  'placement', 'dimensions', 'window', 'modifier', 'impact', 'impressions',
  'clicks', 'ctr', 'budget', 'spent', 'channel', 'period', 'subscribers',
]);

// Priority order for inferred middle columns
const COLUMN_PRIORITY = [
  'type', 'category', 'location', 'customer', 'vendor', 'stars',
  'visitors', 'rooms', 'occupancy', 'stock', 'sold', 'items', 'orders',
  'date', 'rating', 'institutions',
];

interface DataTableProps {
  data: any[];
  onRowClick?: (row: any) => void;
}

function formatCell(key: string, value: any): string {
  if (key === 'rating') return `★ ${value}`;
  if (key === 'stars') return `${'★'.repeat(Number(value))}`;
  return String(value);
}

export default function DataTable({ data, onRowClick }: DataTableProps) {
  const [selected, setSelected] = useState<Set<string>>(new Set());

  const columns = useMemo(() => {
    if (!data.length) return [];
    const keys = Object.keys(data[0]).filter((k) => !RESERVED_KEYS.has(k));
    return COLUMN_PRIORITY.filter((k) => keys.includes(k)).slice(0, 3);
  }, [data]);

  const hasPrice = data.some((r) => typeof r.price === 'number');
  const allSelected = data.length > 0 && selected.size === data.length;

  const toggleAll = () => {
    setSelected(allSelected ? new Set() : new Set(data.map((r) => r.id)));
  };

  const toggleRow = (id: string) => {
    const next = new Set(selected);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setSelected(next);
  };

  if (!data.length) {
    return (
      <div className="py-12 text-center text-sm text-text-muted">No records found.</div>
    );
  }

  return (
    <div className="overflow-x-auto -mx-5 px-5">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-border">
            <th className="w-10 py-3 pl-1">
              <input
                type="checkbox"
                checked={allSelected}
                onChange={toggleAll}
                className="w-4 h-4 rounded border-border bg-surface accent-violet cursor-pointer"
              />
            </th>
            <th className="text-left py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">Item</th>
            {columns.map((col) => (
              <th key={col} className="text-left py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">
                {col}
              </th>
            ))}
            <th className="text-left py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">Status</th>
            {hasPrice && (
              <th className="text-right py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">Price</th>
            )}
            <th className="text-right py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody>
          {data.map((row) => {
            const isSelected = selected.has(row.id);
            return (
              <tr
                key={row.id}
                onClick={() => onRowClick?.(row)}
                className={cn(
                  'border-b border-border/50 transition-colors group',
                  onRowClick && 'cursor-pointer',
                  isSelected ? 'bg-violet/5' : 'hover:bg-surface-hover/60'
                )}
              >
                <td className="py-3 pl-1" onClick={(e) => e.stopPropagation()}>
                  <input
                    type="checkbox"
                    checked={isSelected}
                    onChange={() => toggleRow(row.id)}
                    className="w-4 h-4 rounded border-border bg-surface accent-violet cursor-pointer"
                  />
                </td>
                <td className="py-3 px-3">
                  <div className="flex items-center gap-3 min-w-0">
                    <div className="w-9 h-9 rounded-lg bg-gradient-to-br from-surface-light to-slate-dark border border-border flex items-center justify-center text-base shrink-0">
                      {row.emoji ?? '📁'}
                    </div>
                    <div className="min-w-0">
                      <div className="font-semibold text-white truncate max-w-[220px] group-hover:text-violet transition-colors">
                        {row.name ?? row.title}
                      </div>
                      <div className="text-[11px] text-text-muted font-mono">{row.id}</div>
                    </div>
                  </div>
                </td>
                {columns.map((col) => (
                  <td key={col} className="py-3 px-3 text-text-secondary whitespace-nowrap">
                    {formatCell(col, row[col])}
                  </td>
                ))}
                <td className="py-3 px-3">
                  <span className={cn('pill-badge capitalize', getStatusColor(row.status))}>{row.status}</span>
                </td>
                {hasPrice && (
                  <td className="py-3 px-3 text-right font-semibold text-white whitespace-nowrap">
                    {typeof row.price === 'number' ? formatKES(row.price) : '—'}
                  </td>
                )}
                <td className="py-3 px-3" onClick={(e) => e.stopPropagation()}>
                  <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button className="p-1.5 rounded-md text-text-muted hover:text-cyan hover:bg-cyan/10 transition-all" title="View">
                      <Eye size={14} />
                    </button>
                    <button className="p-1.5 rounded-md text-text-muted hover:text-violet hover:bg-violet/10 transition-all" title="Edit">
                      <Edit3 size={14} />
                    </button>
                    <button className="p-1.5 rounded-md text-text-muted hover:text-rose hover:bg-rose/10 transition-all" title="Delete">
                      <Trash2 size={14} />
                    </button>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>

      {selected.size > 0 && (
        <div className="mt-3 flex items-center justify-between px-3 py-2 rounded-lg bg-violet/10 border border-violet/25 text-xs">
          <span className="text-violet font-semibold">{selected.size} item{selected.size > 1 ? 's' : ''} selected</span>
          <div className="flex gap-2">
            <button className="btn-secondary !py-1 !px-3 !text-xs">Batch Edit</button>
            <button
              onClick={() => setSelected(new Set())}
              className="btn-secondary !py-1 !px-3 !text-xs hover:!border-rose/40 hover:!text-rose"
            >
              Clear
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
