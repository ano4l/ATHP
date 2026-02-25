"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import { CheckCircle2, XCircle, MessageSquare, Clock, AlertTriangle, Send, ChevronDown, ChevronUp } from "lucide-react";

interface Comment {
  id: number;
  author: string;
  text: string;
  time: string;
}

interface Approval {
  ref: string;
  project: string;
  requester: string;
  branch: string;
  amount: string;
  category: string;
  stage: string;
  submitted: string;
  daysOpen: number;
  status: "pending" | "approved" | "denied" | "modification_requested";
  comments: Comment[];
}

const initialApprovals: Approval[] = [
  { ref: "REQ-2026-0247", project: "Copperline Client Rollout", requester: "Jane Employee", branch: "Zambia", amount: "ZMW 5,000.00", category: "Travel", stage: "Stage 1", submitted: "2 hours ago", daysOpen: 0, status: "pending", comments: [] },
  { ref: "REQ-2026-0246", project: "Warehouse PPE Refresh", requester: "Jane Employee", branch: "Zambia", amount: "ZMW 12,500.00", category: "Procurement", stage: "Stage 2", submitted: "5 hours ago", daysOpen: 0, status: "pending", comments: [{ id: 1, author: "Jane Employee", text: "Urgent — PPE stock is critically low.", time: "4 hours ago" }] },
  { ref: "REQ-2026-0250", project: "Client Event Catering", requester: "Admin User", branch: "South Africa", amount: "ZAR 15,000.00", category: "Marketing", stage: "Stage 1", submitted: "1 day ago", daysOpen: 1, status: "pending", comments: [] },
  { ref: "REQ-2026-0251", project: "Server Room Cooling", requester: "Jane Employee", branch: "Zimbabwe", amount: "USD 4,800.00", category: "IT & Software", stage: "Stage 1", submitted: "2 days ago", daysOpen: 2, status: "pending", comments: [] },
];

const statusDisplay: Record<string, { color: string; bg: string; label: string }> = {
  pending: { color: "text-warning", bg: "bg-warning/10", label: "Pending" },
  approved: { color: "text-success", bg: "bg-success/10", label: "Approved" },
  denied: { color: "text-destructive", bg: "bg-destructive/10", label: "Denied" },
  modification_requested: { color: "text-chart-1", bg: "bg-chart-1/10", label: "Modification Requested" },
};

export function ApprovalsSection() {
  const [approvals, setApprovals] = useState<Approval[]>(initialApprovals);
  const [expandedRef, setExpandedRef] = useState<string | null>(null);
  const [commentInputs, setCommentInputs] = useState<Record<string, string>>({});
  const [confirmAction, setConfirmAction] = useState<{ ref: string; action: "approve" | "deny" | "modify" } | null>(null);

  const pendingCount = approvals.filter((a) => a.status === "pending").length;
  const approvedCount = approvals.filter((a) => a.status === "approved").length;
  const overdueCount = approvals.filter((a) => a.status === "pending" && a.daysOpen >= 2).length;

  function handleAction(ref: string, action: "approve" | "deny" | "modify") {
    const comment = commentInputs[ref]?.trim();
    setApprovals((prev) =>
      prev.map((a) => {
        if (a.ref !== ref) return a;
        const newStatus = action === "approve" ? "approved" : action === "deny" ? "denied" : "modification_requested";
        const actionLabel = action === "approve" ? "Approved" : action === "deny" ? "Denied" : "Requested modification";
        const newComments = [...a.comments];
        if (comment) {
          newComments.push({ id: Date.now(), author: "You (Admin)", text: comment, time: "Just now" });
        }
        newComments.push({ id: Date.now() + 1, author: "System", text: `${actionLabel} by Admin User`, time: "Just now" });
        return { ...a, status: newStatus as Approval["status"], comments: newComments };
      })
    );
    setCommentInputs((prev) => ({ ...prev, [ref]: "" }));
    setConfirmAction(null);
  }

  function addComment(ref: string) {
    const text = commentInputs[ref]?.trim();
    if (!text) return;
    setApprovals((prev) =>
      prev.map((a) => {
        if (a.ref !== ref) return a;
        return { ...a, comments: [...a.comments, { id: Date.now(), author: "You (Admin)", text, time: "Just now" }] };
      })
    );
    setCommentInputs((prev) => ({ ...prev, [ref]: "" }));
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-foreground">Pending Approvals</h2>
        <p className="text-sm text-muted-foreground mt-0.5">Requisitions awaiting your review</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-9 h-9 rounded-lg bg-warning/10 flex items-center justify-center">
              <Clock className="w-4 h-4 text-warning" />
            </div>
            <span className="text-sm text-muted-foreground">Awaiting Review</span>
          </div>
          <span className="text-2xl font-bold text-foreground">{pendingCount}</span>
        </div>
        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-9 h-9 rounded-lg bg-success/10 flex items-center justify-center">
              <CheckCircle2 className="w-4 h-4 text-success" />
            </div>
            <span className="text-sm text-muted-foreground">Approved</span>
          </div>
          <span className="text-2xl font-bold text-foreground">{approvedCount}</span>
        </div>
        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-9 h-9 rounded-lg bg-destructive/10 flex items-center justify-center">
              <AlertTriangle className="w-4 h-4 text-destructive" />
            </div>
            <span className="text-sm text-muted-foreground">Overdue (&gt;48h)</span>
          </div>
          <span className="text-2xl font-bold text-foreground">{overdueCount}</span>
        </div>
      </div>

      <div className="space-y-4">
        {approvals.map((req, index) => {
          const isExpanded = expandedRef === req.ref;
          const isPending = req.status === "pending";
          const sd = statusDisplay[req.status];
          const isConfirming = confirmAction?.ref === req.ref;

          return (
            <div
              key={req.ref}
              className={cn(
                "bg-card border rounded-xl p-5 transition-all duration-300 animate-in fade-in slide-in-from-bottom-4",
                isPending ? "border-border hover:border-accent/50" : "border-border opacity-80"
              )}
              style={{ animationDelay: `${index * 100}ms`, animationFillMode: "both" }}
            >
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <span className="text-sm font-semibold text-foreground">{req.ref}</span>
                    <span className="text-xs px-2 py-0.5 rounded-md bg-chart-1/10 text-chart-1 font-medium">{req.stage}</span>
                    <span className={cn("text-xs px-2 py-0.5 rounded-md font-medium", sd.bg, sd.color)}>{sd.label}</span>
                    {req.daysOpen >= 2 && isPending && (
                      <span className="text-xs px-2 py-0.5 rounded-md bg-destructive/10 text-destructive font-medium">Overdue</span>
                    )}
                  </div>
                  <p className="text-sm text-foreground font-medium">{req.project}</p>
                  <p className="text-xs text-muted-foreground mt-1">
                    {req.requester} • {req.branch} • {req.category} • {req.submitted}
                  </p>
                </div>
                <span className="text-lg font-bold text-foreground">{req.amount}</span>
              </div>

              {/* Action buttons */}
              {isPending && (
                <div className="mt-4 pt-4 border-t border-border">
                  {isConfirming ? (
                    <div className="space-y-3">
                      <p className="text-sm text-foreground font-medium">
                        {confirmAction.action === "approve" && "Confirm approval of this requisition?"}
                        {confirmAction.action === "deny" && "Confirm denial of this requisition?"}
                        {confirmAction.action === "modify" && "Request modification for this requisition?"}
                      </p>
                      <textarea
                        value={commentInputs[req.ref] || ""}
                        onChange={(e) => setCommentInputs((prev) => ({ ...prev, [req.ref]: e.target.value }))}
                        placeholder={confirmAction.action === "deny" ? "Reason for denial (required)..." : "Add a comment (optional)..."}
                        rows={2}
                        className="w-full px-3 py-2 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent resize-none"
                      />
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => {
                            if (confirmAction.action === "deny" && !commentInputs[req.ref]?.trim()) return;
                            handleAction(req.ref, confirmAction.action);
                          }}
                          disabled={confirmAction.action === "deny" && !commentInputs[req.ref]?.trim()}
                          className={cn(
                            "flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors",
                            confirmAction.action === "approve" && "bg-success text-white hover:bg-success/90",
                            confirmAction.action === "deny" && "bg-destructive text-white hover:bg-destructive/90 disabled:opacity-50 disabled:cursor-not-allowed",
                            confirmAction.action === "modify" && "bg-chart-1 text-white hover:bg-chart-1/90"
                          )}
                        >
                          <CheckCircle2 className="w-4 h-4" />
                          Confirm
                        </button>
                        <button
                          onClick={() => setConfirmAction(null)}
                          className="px-4 py-2 rounded-lg bg-secondary text-muted-foreground text-sm font-medium hover:text-foreground transition-colors"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="flex items-center gap-2">
                      <button
                        onClick={() => setConfirmAction({ ref: req.ref, action: "approve" })}
                        className="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-success/10 text-success text-sm font-medium hover:bg-success/20 transition-colors"
                      >
                        <CheckCircle2 className="w-4 h-4" />
                        Approve
                      </button>
                      <button
                        onClick={() => setConfirmAction({ ref: req.ref, action: "deny" })}
                        className="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-destructive/10 text-destructive text-sm font-medium hover:bg-destructive/20 transition-colors"
                      >
                        <XCircle className="w-4 h-4" />
                        Deny
                      </button>
                      <button
                        onClick={() => setConfirmAction({ ref: req.ref, action: "modify" })}
                        className="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-secondary text-muted-foreground text-sm font-medium hover:text-foreground hover:bg-secondary/80 transition-colors"
                      >
                        <MessageSquare className="w-4 h-4" />
                        Request Modification
                      </button>
                    </div>
                  )}
                </div>
              )}

              {/* Comments section */}
              <div className="mt-3">
                <button
                  onClick={() => setExpandedRef(isExpanded ? null : req.ref)}
                  className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors"
                >
                  <MessageSquare className="w-3.5 h-3.5" />
                  {req.comments.length > 0 ? `${req.comments.length} comment${req.comments.length !== 1 ? "s" : ""}` : "Add comment"}
                  {isExpanded ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                </button>

                {isExpanded && (
                  <div className="mt-3 space-y-3 animate-in fade-in slide-in-from-top-2 duration-200">
                    {req.comments.length > 0 && (
                      <div className="space-y-2 max-h-48 overflow-y-auto">
                        {req.comments.map((c) => (
                          <div key={c.id} className={cn("px-3 py-2 rounded-lg text-sm", c.author === "System" ? "bg-secondary/50 text-muted-foreground italic" : "bg-secondary")}>
                            <div className="flex items-center justify-between mb-0.5">
                              <span className="text-xs font-semibold text-foreground">{c.author}</span>
                              <span className="text-[10px] text-muted-foreground">{c.time}</span>
                            </div>
                            <p className="text-sm text-foreground">{c.text}</p>
                          </div>
                        ))}
                      </div>
                    )}
                    <div className="flex items-center gap-2">
                      <input
                        type="text"
                        value={commentInputs[req.ref] || ""}
                        onChange={(e) => setCommentInputs((prev) => ({ ...prev, [req.ref]: e.target.value }))}
                        onKeyDown={(e) => e.key === "Enter" && addComment(req.ref)}
                        placeholder="Write a comment..."
                        className="flex-1 h-9 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent"
                      />
                      <button
                        onClick={() => addComment(req.ref)}
                        disabled={!commentInputs[req.ref]?.trim()}
                        className="w-9 h-9 flex items-center justify-center rounded-lg bg-accent text-accent-foreground hover:bg-accent/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <Send className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
