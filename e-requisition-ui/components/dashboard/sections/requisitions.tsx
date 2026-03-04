"use client";

import { useState, useEffect, useCallback } from "react";
import { cn } from "@/lib/utils";
import { Plus, Search, Filter, Loader2, RefreshCw } from "lucide-react";
import { RequisitionForm } from "@/components/dashboard/modals/requisition-form";
import { getRequisitions } from "@/lib/api";

interface Requisition {
  id: number;
  reference_no: string;
  project_name: string;
  category: string;
  branch: string;
  amount: string;
  currency: string;
  status: string;
  created_at: string;
  requester?: { name: string };
}

const statusConfig: Record<string, { color: string; bg: string; label: string }> = {
  draft: { color: "text-muted-foreground", bg: "bg-muted/50", label: "Draft" },
  submitted: { color: "text-warning", bg: "bg-warning/10", label: "Submitted" },
  stage1_approved: { color: "text-chart-1", bg: "bg-chart-1/10", label: "Stage 1" },
  modification_requested: { color: "text-chart-3", bg: "bg-chart-3/10", label: "Modify" },
  approved: { color: "text-success", bg: "bg-success/10", label: "Approved" },
  processing: { color: "text-chart-1", bg: "bg-chart-1/10", label: "Processing" },
  paid: { color: "text-chart-2", bg: "bg-chart-2/10", label: "Paid" },
  outstanding: { color: "text-warning", bg: "bg-warning/10", label: "Outstanding" },
  fulfilled: { color: "text-success", bg: "bg-success/10", label: "Fulfilled" },
  closed: { color: "text-muted-foreground", bg: "bg-muted/50", label: "Closed" },
  denied: { color: "text-destructive", bg: "bg-destructive/10", label: "Denied" },
};

const STATUSES = ["all", "draft", "submitted", "stage1_approved", "approved", "processing", "paid", "fulfilled", "closed", "denied"];

export function RequisitionsSection() {
  const [formOpen, setFormOpen] = useState(false);
  const [requisitions, setRequisitions] = useState<Requisition[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getRequisitions(statusFilter === "all" ? undefined : statusFilter, page);
      setRequisitions(res.data ?? []);
      setLastPage(res.last_page ?? 1);
    } catch (e) {
      console.error("Failed to load requisitions", e);
    } finally {
      setLoading(false);
    }
  }, [statusFilter, page]);

  useEffect(() => { load(); }, [load]);

  const filtered = search
    ? requisitions.filter(r =>
        r.reference_no?.toLowerCase().includes(search.toLowerCase()) ||
        r.project_name?.toLowerCase().includes(search.toLowerCase())
      )
    : requisitions;

  function fmtDate(d: string) {
    return new Date(d).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
  }

  return (
    <div className="space-y-6">
      <RequisitionForm open={formOpen} onClose={() => { setFormOpen(false); load(); }} />

      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold text-foreground">All Requisitions</h2>
          <p className="text-sm text-muted-foreground mt-0.5">Manage requisitions</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={load} className="w-9 h-9 flex items-center justify-center rounded-lg border border-border text-muted-foreground hover:text-foreground transition-colors">
            <RefreshCw className={cn("w-4 h-4", loading && "animate-spin")} />
          </button>
          <button
            onClick={() => setFormOpen(true)}
            className="flex items-center gap-2 px-4 py-2.5 bg-accent text-accent-foreground rounded-lg text-sm font-medium hover:bg-accent/90 transition-colors"
          >
            <Plus className="w-4 h-4" /> New Requisition
          </button>
        </div>
      </div>

      <div className="flex items-center gap-3 flex-wrap">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search by reference or project..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="w-full h-10 pl-9 pr-4 rounded-lg bg-card border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent transition-all"
          />
        </div>
        <select
          value={statusFilter}
          onChange={e => { setStatusFilter(e.target.value); setPage(1); }}
          className="h-10 px-3 rounded-lg bg-card border border-border text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent"
        >
          {STATUSES.map(s => (
            <option key={s} value={s}>{s === "all" ? "All Statuses" : (statusConfig[s]?.label ?? s)}</option>
          ))}
        </select>
      </div>

      <div className="bg-card border border-border rounded-xl overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
          </div>
        ) : filtered.length === 0 ? (
          <div className="text-center py-16 text-sm text-muted-foreground">No requisitions found.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border">
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Reference</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Project</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Branch</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                  <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.map((req, index) => {
                  const sc = statusConfig[req.status] ?? { color: "text-muted-foreground", bg: "bg-muted/50", label: req.status };
                  return (
                    <tr
                      key={req.id}
                      className="hover:bg-secondary/30 transition-colors cursor-pointer animate-in fade-in slide-in-from-bottom-2"
                      style={{ animationDelay: `${index * 30}ms`, animationFillMode: "both" }}
                    >
                      <td className="px-4 py-3 font-medium text-foreground">{req.reference_no}</td>
                      <td className="px-4 py-3 text-foreground">{req.project_name}</td>
                      <td className="px-4 py-3 text-muted-foreground capitalize">{req.branch?.replace(/_/g, " ")}</td>
                      <td className="px-4 py-3 font-semibold text-foreground">{req.currency} {Number(req.amount).toLocaleString("en", { minimumFractionDigits: 2 })}</td>
                      <td className="px-4 py-3">
                        <span className={cn("inline-flex items-center px-2 py-1 rounded-md text-xs font-medium", sc.bg, sc.color)}>{sc.label}</span>
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">{fmtDate(req.created_at)}</td>
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
