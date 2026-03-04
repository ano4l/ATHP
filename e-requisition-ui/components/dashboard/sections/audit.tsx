"use client";

import { useState, useEffect, useCallback } from "react";
import { cn } from "@/lib/utils";
import { Loader2, RefreshCw, Activity } from "lucide-react";
import { getAuditLog } from "@/lib/api";

interface AuditEvent {
  id: number;
  auditable_type: string;
  auditable_id: number;
  action: string;
  actor_id: number;
  metadata: Record<string, any> | null;
  created_at: string;
  actor?: { name: string };
}

const actionColors: Record<string, string> = {
  created: "text-chart-1",
  submitted: "text-warning",
  stage1_approved: "text-success",
  approved: "text-success",
  denied: "text-destructive",
  modification_requested: "text-chart-3",
  processing: "text-chart-1",
  paid: "text-chart-2",
  fulfilled: "text-success",
  closed: "text-muted-foreground",
  attachment_uploaded: "text-chart-4",
  comment_added: "text-chart-5",
};

function formatAction(action: string): string {
  return action.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase());
}

function fmtDate(d: string) {
  return new Date(d).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

export function AuditSection() {
  const [events, setEvents] = useState<AuditEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getAuditLog(page);
      setEvents(res.data ?? []);
      setLastPage(res.last_page ?? 1);
    } catch (e) {
      console.error("Failed to load audit log", e);
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { load(); }, [load]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold text-foreground">Audit Trail</h2>
          <p className="text-sm text-muted-foreground mt-0.5">Complete history of all system actions</p>
        </div>
        <button onClick={load} className="w-9 h-9 flex items-center justify-center rounded-lg border border-border text-muted-foreground hover:text-foreground transition-colors">
          <RefreshCw className={cn("w-4 h-4", loading && "animate-spin")} />
        </button>
      </div>

      <div className="bg-card border border-border rounded-xl overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
          </div>
        ) : events.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-16 text-muted-foreground">
            <Activity className="w-8 h-8 mb-2 opacity-40" />
            <p className="text-sm">No audit events recorded yet.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border">
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Action</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Entity</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Actor</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Timestamp</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {events.map((event, index) => {
                  const color = actionColors[event.action] ?? "text-muted-foreground";
                  return (
                    <tr
                      key={event.id}
                      className="hover:bg-secondary/30 transition-colors animate-in fade-in slide-in-from-bottom-2"
                      style={{ animationDelay: `${index * 30}ms`, animationFillMode: "both" }}
                    >
                      <td className="px-4 py-3">
                        <span className={cn("font-medium", color)}>{formatAction(event.action)}</span>
                      </td>
                      <td className="px-4 py-3 font-mono text-sm text-foreground">
                        {event.auditable_type?.split("\\").pop()} #{event.auditable_id}
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">{event.actor?.name ?? `User #${event.actor_id}`}</td>
                      <td className="px-4 py-3 text-muted-foreground">{fmtDate(event.created_at)}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {lastPage > 1 && (
        <div className="flex items-center justify-center gap-2">
          <button disabled={page <= 1} onClick={() => setPage(p => p - 1)} className="px-3 py-1.5 rounded-lg text-sm border border-border text-muted-foreground hover:text-foreground disabled:opacity-40">Previous</button>
          <span className="text-sm text-muted-foreground">Page {page} of {lastPage}</span>
          <button disabled={page >= lastPage} onClick={() => setPage(p => p + 1)} className="px-3 py-1.5 rounded-lg text-sm border border-border text-muted-foreground hover:text-foreground disabled:opacity-40">Next</button>
        </div>
      )}
    </div>
  );
}
