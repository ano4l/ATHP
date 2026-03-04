"use client";

import { useState, useEffect, useCallback } from "react";
import { cn } from "@/lib/utils";
import {
  CheckCircle2, XCircle, MessageSquare, Clock,
  ChevronDown, ChevronUp, AlertCircle, Loader2, RefreshCw
} from "lucide-react";
import { getRequisitions, approveRequisition, denyRequisition, requestModification } from "@/lib/api";

interface Requisition {
  id: number;
  reference_no: string;
  project_name: string;
  category: string;
  branch: string;
  amount: string;
  currency: string;
  status: string;
  purpose: string;
  cost_center: string;
  budget_code: string;
  needed_by: string;
  created_at: string;
  requester?: { name: string; email: string };
}

export function ApprovalsSection() {
  const [requisitions, setRequisitions] = useState<Requisition[]>([]);
  const [loading, setLoading] = useState(true);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [comments, setComments] = useState<Record<number, string>>({});
  const [confirmAction, setConfirmAction] = useState<{ id: number; action: "approve" | "deny" | "modify" } | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [submitted, stage1] = await Promise.all([
        getRequisitions("submitted", 1),
        getRequisitions("stage1_approved", 1),
      ]);
      setRequisitions([...(submitted.data ?? []), ...(stage1.data ?? [])]);
    } catch (e) {
      console.error("Failed to load approvals", e);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  async function handleAction(id: number, action: "approve" | "deny" | "modify") {
    setActionLoading(true);
    const comment = comments[id] || "";
    try {
      if (action === "approve") await approveRequisition(id, comment || undefined);
      else if (action === "deny") await denyRequisition(id, comment);
      else await requestModification(id, comment);

      setRequisitions(prev => prev.filter(r => r.id !== id));
      setConfirmAction(null);
      setComments(prev => ({ ...prev, [id]: "" }));
    } catch (e: any) {
      alert(e.message || "Action failed");
    } finally {
      setActionLoading(false);
    }
  }

  function fmtDate(d: string) {
    return new Date(d).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
  }

  const submittedCount = requisitions.filter(r => r.status === "submitted").length;
  const stage1Count = requisitions.filter(r => r.status === "stage1_approved").length;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold text-foreground">Pending Approvals</h2>
          <p className="text-sm text-muted-foreground mt-0.5">Review and action requisition requests</p>
        </div>
        <button onClick={load} className="w-9 h-9 flex items-center justify-center rounded-lg border border-border text-muted-foreground hover:text-foreground transition-colors">
          <RefreshCw className={cn("w-4 h-4", loading && "animate-spin")} />
        </button>
      </div>

      <div className="grid grid-cols-3 gap-4">
        {[
          { label: "Awaiting Stage 1", count: submittedCount, color: "text-warning" },
          { label: "Awaiting Final", count: stage1Count, color: "text-chart-1" },
          { label: "Total Pending", count: requisitions.length, color: "text-foreground" },
        ].map(s => (
          <div key={s.label} className="bg-card border border-border rounded-xl p-4 text-center">
            <span className={cn("text-2xl font-bold", s.color)}>{s.count}</span>
            <p className="text-xs text-muted-foreground mt-1">{s.label}</p>
          </div>
        ))}
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-16">
          <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
        </div>
      ) : requisitions.length === 0 ? (
        <div className="bg-card border border-border rounded-xl p-12 text-center">
          <CheckCircle2 className="w-8 h-8 text-success mx-auto mb-2 opacity-60" />
          <p className="text-sm text-muted-foreground">No pending approvals. All caught up!</p>
        </div>
      ) : (
        <div className="space-y-4">
          {requisitions.map((req, index) => {
            const isExpanded = expandedId === req.id;

            return (
              <div
                key={req.id}
                className="bg-card border border-border rounded-xl overflow-hidden animate-in fade-in slide-in-from-bottom-4"
                style={{ animationDelay: `${index * 80}ms`, animationFillMode: "both" }}
              >
                <div
                  className="flex items-center justify-between p-4 cursor-pointer hover:bg-secondary/30 transition-colors"
                  onClick={() => setExpandedId(isExpanded ? null : req.id)}
                >
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-lg flex items-center justify-center bg-warning/10">
                      <Clock className="w-5 h-5 text-warning" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-sm font-semibold text-foreground">{req.reference_no}</span>
                        <span className={cn(
                          "text-xs px-2 py-0.5 rounded-full font-medium",
                          req.status === "submitted" ? "bg-warning/10 text-warning" : "bg-chart-1/10 text-chart-1"
                        )}>
                          {req.status === "submitted" ? "Stage 1" : "Final Approval"}
                        </span>
                      </div>
                      <p className="text-xs text-muted-foreground mt-0.5">{req.project_name} · {req.requester?.name ?? "Unknown"}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-4">
                    <span className="text-sm font-semibold text-foreground">{req.currency} {Number(req.amount).toLocaleString("en", { minimumFractionDigits: 2 })}</span>
                    {isExpanded ? <ChevronUp className="w-4 h-4 text-muted-foreground" /> : <ChevronDown className="w-4 h-4 text-muted-foreground" />}
                  </div>
                </div>

                {isExpanded && (
                  <div className="px-4 pb-4 space-y-4 border-t border-border pt-4">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                      {([
                        ["Branch", req.branch?.replace(/_/g, " ")],
                        ["Category", req.category?.replace(/_/g, " ")],
                        ["Cost Centre", req.cost_center || "—"],
                        ["Needed By", req.needed_by ? fmtDate(req.needed_by) : "—"],
                      ] as const).map(([k, v]) => (
                        <div key={k}>
                          <p className="text-xs text-muted-foreground">{k}</p>
                          <p className="text-sm font-medium text-foreground capitalize">{v}</p>
                        </div>
                      ))}
                    </div>

                    <div>
                      <p className="text-xs text-muted-foreground mb-1">Business Justification</p>
                      <p className="text-sm text-foreground bg-secondary rounded-lg p-3">{req.purpose}</p>
                    </div>

                    <div className="space-y-2">
                      <p className="text-xs text-muted-foreground">Add Comment</p>
                      <textarea
                        rows={2}
                        className="w-full px-3 py-2 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent resize-none transition-all"
                        placeholder="Add a comment or reason..."
                        value={comments[req.id] || ""}
                        onChange={e => setComments(prev => ({ ...prev, [req.id]: e.target.value }))}
                      />
                    </div>

                    {confirmAction?.id === req.id ? (
                      <div className="flex items-center gap-3 p-3 rounded-lg bg-warning/10 border border-warning/30">
                        <AlertCircle className="w-4 h-4 text-warning shrink-0" />
                        <p className="text-xs text-foreground flex-1">
                          Confirm <strong>{confirmAction.action}</strong> for {req.reference_no}?
                          {confirmAction.action === "deny" && !comments[req.id]?.trim() && (
                            <span className="text-destructive ml-1">(Comment required for denial)</span>
                          )}
                        </p>
                        <button
                          onClick={() => handleAction(req.id, confirmAction.action)}
                          disabled={actionLoading || (confirmAction.action === "deny" && !comments[req.id]?.trim())}
                          className="px-3 py-1.5 rounded-lg bg-accent text-accent-foreground text-xs font-medium hover:bg-accent/90 disabled:opacity-50"
                        >
                          {actionLoading ? "..." : "Confirm"}
                        </button>
                        <button
                          onClick={() => setConfirmAction(null)}
                          className="px-3 py-1.5 rounded-lg border border-border text-xs font-medium text-muted-foreground hover:text-foreground"
                        >
                          Cancel
                        </button>
                      </div>
                    ) : (
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => setConfirmAction({ id: req.id, action: "approve" })}
                          className="flex items-center gap-2 px-4 py-2 rounded-lg bg-success/10 text-success text-sm font-medium hover:bg-success/20 transition-colors"
                        >
                          <CheckCircle2 className="w-4 h-4" /> Approve
                        </button>
                        <button
                          onClick={() => setConfirmAction({ id: req.id, action: "deny" })}
                          className="flex items-center gap-2 px-4 py-2 rounded-lg bg-destructive/10 text-destructive text-sm font-medium hover:bg-destructive/20 transition-colors"
                        >
                          <XCircle className="w-4 h-4" /> Deny
                        </button>
                        <button
                          onClick={() => {
                            if (!comments[req.id]?.trim()) {
                              alert("Please add a comment describing what modifications are needed.");
                              return;
                            }
                            setConfirmAction({ id: req.id, action: "modify" });
                          }}
                          className="flex items-center gap-2 px-4 py-2 rounded-lg bg-secondary text-muted-foreground text-sm font-medium hover:text-foreground transition-colors"
                        >
                          <MessageSquare className="w-4 h-4" /> Request Modification
                        </button>
                      </div>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
